<?php

namespace App\ArtificialIntelligence;

use App\ArtificialIntelligence\Interaction\AiInteractionRecorder;
use App\ArtificialIntelligence\Provider\AiProviderInterface;
use App\ArtificialIntelligence\Provider\AiProviderResolver;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\ResultInterface;
use App\ArtificialIntelligence\Tool\ToolCall;
use App\ArtificialIntelligence\Tool\ToolExecutor;
use App\ArtificialIntelligence\Tool\ToolExecutionResult;
use App\ArtificialIntelligence\Tool\ToolRuntimeContext;
use App\Entity\Assistant;
use App\Entity\Conversation;

final class AiGateway
{
    private const MAX_TOOL_ITERATIONS = 5;

    public function __construct(
        private readonly AiProviderResolver $resolver,
        private readonly AiInteractionRecorder $interactionRecorder,
        private readonly ToolExecutor $toolExecutor,
    ) {
    }

    public function execute(
        PromptInterface $prompt,
        ?Assistant $assistant = null,
        ?Conversation $conversation = null,
        ?string $providerName = null,
    ): ResultInterface {
        $resolvedAssistant = $assistant ?? $conversation?->getAssistant();

        if ($providerName !== null) {
            $provider = $this->resolver->resolveForPrompt($prompt, $providerName, $resolvedAssistant);

            return $this->processWithLogging($provider, $prompt, $resolvedAssistant, $conversation);
        }

        if ($conversation !== null) {
            $provider = $this->resolver->resolveForConversation($prompt, $conversation);

            return $this->processWithLogging($provider, $prompt, $resolvedAssistant, $conversation);
        }

        $provider = $this->resolver->resolveForPrompt($prompt, null, $resolvedAssistant);

        return $this->processWithLogging($provider, $prompt, $resolvedAssistant, $conversation);
    }

    private function processWithLogging(
        AiProviderInterface $provider,
        PromptInterface $prompt,
        ?Assistant $assistant,
        ?Conversation $conversation,
    ): ResultInterface {
        $providerName = $provider->getName();
        $currentPrompt = $prompt;
        $toolExecutions = [];
        $providerIterations = [];
        $iteration = 0;

        try {
            while (true) {
                if ($iteration >= self::MAX_TOOL_ITERATIONS) {
                    throw new \RuntimeException(sprintf('Tool execution exceeded the maximum of %d iterations.', self::MAX_TOOL_ITERATIONS));
                }

                $response = $provider->process($currentPrompt);
                $providerIterations[] = [
                    'iteration' => $iteration,
                    'metadata' => $response->getMetadata(),
                ];

                if ($response->hasResult()) {
                    $result = $response->getResult();

                    if ($result === null) {
                        throw new \RuntimeException('AI provider returned an empty result.');
                    }

                    $metadataOverrides = $this->buildMetadataOverrides($toolExecutions, $providerIterations);
                    $this->interactionRecorder->recordSuccess(
                        $currentPrompt,
                        $result,
                        $providerName,
                        $assistant,
                        $conversation,
                        metadataOverrides: $metadataOverrides,
                    );

                    return $result;
                }

                $toolCalls = $response->getToolCalls();

                if ($toolCalls === []) {
                    throw new \RuntimeException(sprintf('Provider %s requested continuation without tool calls.', $providerName));
                }

                if (!$currentPrompt instanceof TextPrompt) {
                    throw new \RuntimeException(sprintf(
                        'Provider %s requested tool execution for unsupported prompt type %s.',
                        $providerName,
                        $currentPrompt->getType()->value,
                    ));
                }

                $currentPrompt = $this->handleToolContinuation(
                    $currentPrompt,
                    $toolCalls,
                    $assistant,
                    $conversation,
                    $providerName,
                    $iteration,
                    $toolExecutions,
                );

                $iteration++;
            }
        } catch (\Throwable $exception) {
            $metadataOverrides = $this->buildMetadataOverrides($toolExecutions, $providerIterations);
            $this->recordFailureWithMetadata($currentPrompt, $providerName, $exception, $assistant, $conversation, $metadataOverrides);

            throw $exception;
        }
    }

    /**
     * @param list<ToolCall> $toolCalls
     * @param list<array<string, mixed>> $toolExecutionsLog
     */
    private function handleToolContinuation(
        TextPrompt $prompt,
        array $toolCalls,
        ?Assistant $assistant,
        ?Conversation $conversation,
        string $providerName,
        int $iteration,
        array &$toolExecutionsLog,
    ): TextPrompt {
        $context = new ToolRuntimeContext($assistant, $conversation);
        $executions = [];

        foreach ($toolCalls as $call) {
            $result = $this->toolExecutor->execute($call, $context);
            $executions[] = [
                'call' => $call,
                'result' => $result,
            ];

            $toolExecutionsLog[] = [
                'iteration' => $iteration,
                'call_id' => $call->getCallId(),
                'tool' => $call->getName(),
                'arguments' => $call->getArguments(),
                'result' => [
                    'content' => $result->getContent(),
                    'metadata' => $result->getMetadata(),
                ],
            ];
        }

        return $this->appendToolResultsToPrompt($prompt, $executions, $providerName);
    }

    /**
     * @param list<array{call: ToolCall, result: ToolExecutionResult}> $executions
     */
    private function appendToolResultsToPrompt(TextPrompt $prompt, array $executions, string $providerName): TextPrompt
    {
        if ($executions === []) {
            return $prompt;
        }

        $messages = $prompt->getMessages();

        foreach ($executions as $execution) {
            $call = $execution['call'];
            $result = $execution['result'];
            $callId = $call->getCallId();

            if ($callId === null || $callId === '') {
                throw new \RuntimeException(sprintf('Provider %s returned a tool call without an identifier.', $providerName));
            }

            $messages[] = new TextPromptMessage(
                TextPromptRole::TOOL,
                $result->getContent(),
                [
                    'tool_call_id' => $callId,
                    'tool_name' => $call->getName(),
                ],
            );
        }

        return new TextPrompt(
            $messages,
            $prompt->getTemperature(),
            $prompt->getMaxOutputTokens(),
            $prompt->getMetadata(),
            $prompt->getTools(),
        );
    }

    /**
     * @param list<array<string, mixed>> $toolExecutions
     * @param list<array<string, mixed>> $providerIterations
     *
     * @return array<string, mixed>
     */
    private function buildMetadataOverrides(array $toolExecutions, array $providerIterations): array
    {
        $overrides = [];

        if ($providerIterations !== []) {
            $overrides['provider_iterations'] = $providerIterations;
        }

        if ($toolExecutions !== []) {
            $overrides['tools'] = $toolExecutions;
        }

        return $overrides;
    }

    /**
     * @param array<string, mixed> $metadataOverrides
     */
    private function recordFailureWithMetadata(
        PromptInterface $prompt,
        string $providerName,
        \Throwable $exception,
        ?Assistant $assistant,
        ?Conversation $conversation,
        array $metadataOverrides,
    ): void {
        $loggingFailure = null;

        try {
            $this->interactionRecorder->recordFailure(
                $prompt,
                $providerName,
                $exception,
                $assistant,
                $conversation,
                metadataOverrides: $metadataOverrides,
            );
        } catch (\Throwable $loggingException) {
            $loggingFailure = $loggingException;
        }

        if ($loggingFailure !== null) {
            throw new \RuntimeException(
                sprintf(
                    'AI interaction failed (%s: %s) and logging also failed (%s: %s).',
                    $exception::class,
                    $exception->getMessage(),
                    $loggingFailure::class,
                    $loggingFailure->getMessage(),
                ),
                0,
                $exception,
            );
        }
    }
}
