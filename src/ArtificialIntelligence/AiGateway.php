<?php

namespace App\ArtificialIntelligence;

use App\ArtificialIntelligence\Interaction\AiInteractionRecorder;
use App\ArtificialIntelligence\Provider\AiProviderInterface;
use App\ArtificialIntelligence\Provider\AiProviderResolver;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Result\ResultInterface;
use App\Entity\Assistant;
use App\Entity\Conversation;

final class AiGateway
{
    public function __construct(
        private readonly AiProviderResolver $resolver,
        private readonly AiInteractionRecorder $interactionRecorder,
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

        try {
            $result = $provider->process($prompt);
            $this->interactionRecorder->recordSuccess($prompt, $result, $providerName, $assistant, $conversation);

            return $result;
        } catch (\Throwable $exception) {
            $loggingFailure = null;

            try {
                $this->interactionRecorder->recordFailure($prompt, $providerName, $exception, $assistant, $conversation);
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

            throw $exception;
        }
    }
}
