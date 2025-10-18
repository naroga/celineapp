<?php

namespace App\Tests\ArtificialIntelligence;

use App\ArtificialIntelligence\AiGateway;
use App\ArtificialIntelligence\Interaction\AiInteractionRecorder;
use App\ArtificialIntelligence\Provider\AiProviderInterface;
use App\ArtificialIntelligence\Provider\AiProviderRegistry;
use App\ArtificialIntelligence\Provider\AiProviderResolver;
use App\ArtificialIntelligence\Provider\ProviderResponse;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\TextResult;
use App\ArtificialIntelligence\Tool\ToolCall;
use App\ArtificialIntelligence\Tool\ToolDefinition;
use App\ArtificialIntelligence\Tool\ToolDefinitionType;
use App\ArtificialIntelligence\Tool\ToolExecutionResult;
use App\ArtificialIntelligence\Tool\ToolExecutor;
use App\ArtificialIntelligence\Tool\ToolHandlerInterface;
use App\ArtificialIntelligence\Tool\ToolRegistry;
use App\ArtificialIntelligence\Tool\ToolRuntimeContext;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AiGatewayTest extends TestCase
{
    public function testExecutesToolCallsBeforeReturningFinalResult(): void
    {
        $toolDefinition = new ToolDefinition(
            'inspect',
            'Inspect a resource.',
            [
                'type' => 'object',
                'properties' => [
                    'target' => ['type' => 'string'],
                ],
                'required' => ['target'],
            ],
            ToolDefinitionType::FUNCTION,
        );

        $prompt = new TextPrompt([
            new TextPromptMessage(TextPromptRole::USER, 'Please inspect the foo resource.'),
        ], null, null, [], [$toolDefinition]);

        $finalResult = new TextResult('sequence-mock', 'Inspection complete.');

        $provider = new class($finalResult) implements AiProviderInterface {
            /** @var list<ProviderResponse> */
            private array $responses;

            /** @var list<PromptInterface> */
            public array $capturedPrompts = [];

            public function __construct(TextResult $finalResult)
            {
                $this->responses = [
                    ProviderResponse::fromToolCalls([
                        new ToolCall('inspect', ['target' => 'foo'], 'call-1'),
                    ], ['stage' => 'tool-request']),
                    ProviderResponse::fromResult($finalResult, ['stage' => 'final']),
                ];
            }

            public function getName(): string
            {
                return 'sequence-mock';
            }

            public function supports(PromptInterface $prompt): bool
            {
                return $prompt->getType() === PromptType::TEXT;
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                $this->capturedPrompts[] = $prompt;

                if ($this->responses === []) {
                    throw new \RuntimeException('No responses remaining for provider stub.');
                }

                return array_shift($this->responses);
            }
        };

        $registry = new AiProviderRegistry([$provider]);
        $resolver = new AiProviderResolver($registry, ['default' => 'sequence-mock']);

        $recordedMetadata = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity) use (&$recordedMetadata) {
                self::assertInstanceOf(\App\Entity\AiInteraction::class, $entity);
                $recordedMetadata = $entity->getMetadata();

                return true;
            }));
        $entityManager->expects(self::once())->method('flush');

        $recorder = new AiInteractionRecorder($entityManager);

        $recordingHandler = new class($toolDefinition) implements ToolHandlerInterface {
            public array $executions = [];

            public function __construct(private readonly ToolDefinition $definition)
            {
            }

            public function getDefinition(): ToolDefinition
            {
                return $this->definition;
            }

            public function execute(ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult
            {
                $this->executions[] = ['call' => $call, 'context' => $context];

                return new ToolExecutionResult('{"status":"ok"}');
            }
        };

        $toolExecutor = new ToolExecutor(new ToolRegistry([$recordingHandler]));

        $gateway = new AiGateway($resolver, $recorder, $toolExecutor);

        $result = $gateway->execute($prompt);

        self::assertSame($finalResult, $result);
        self::assertCount(2, $provider->capturedPrompts);

        $secondPrompt = $provider->capturedPrompts[1];
        self::assertInstanceOf(TextPrompt::class, $secondPrompt);
        $secondMessages = $secondPrompt->getMessages();
        $lastMessage = $secondMessages[array_key_last($secondMessages)];
        self::assertInstanceOf(TextPromptMessage::class, $lastMessage);
        self::assertSame(TextPromptRole::TOOL, $lastMessage->getRole());
        self::assertSame('inspect', $lastMessage->getMetadata()['tool_name']);
        self::assertSame('{"status":"ok"}', $lastMessage->getContent());

        self::assertCount(1, $recordingHandler->executions);
        $executedCall = $recordingHandler->executions[0]['call'];
        self::assertInstanceOf(ToolCall::class, $executedCall);
        self::assertSame('inspect', $executedCall->getName());
        self::assertSame(['target' => 'foo'], $executedCall->getArguments());
        self::assertSame('call-1', $executedCall->getCallId());

        /** @var ToolRuntimeContext $executionContext */
        $executionContext = $recordingHandler->executions[0]['context'];
        self::assertInstanceOf(ToolRuntimeContext::class, $executionContext);
        self::assertNull($executionContext->getAssistant());
        self::assertNull($executionContext->getConversation());

        self::assertIsArray($recordedMetadata);
        self::assertArrayHasKey('overrides', $recordedMetadata);
        self::assertIsArray($recordedMetadata['overrides']);
        self::assertArrayHasKey('tools', $recordedMetadata['overrides']);
        self::assertSame('inspect', $recordedMetadata['overrides']['tools'][0]['tool'] ?? null);
    }
}
