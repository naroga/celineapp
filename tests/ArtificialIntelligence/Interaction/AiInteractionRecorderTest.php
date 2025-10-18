<?php

namespace App\Tests\ArtificialIntelligence\Interaction;

use App\ArtificialIntelligence\Interaction\AiInteractionRecorder;
use App\ArtificialIntelligence\Interaction\AiInteractionStatus;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\TextResult;
use App\Entity\Assistant;
use App\Entity\Conversation;
use App\Entity\User;
use App\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AiInteractionRecorderTest extends TestCase
{
    public function testRecordSuccessPersistsInteractionWithTokens(): void
    {
        $capturedInteraction = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(function ($interaction) use (&$capturedInteraction) {
                $capturedInteraction = $interaction;

                return $interaction instanceof \App\Entity\AiInteraction;
            }));
        $entityManager->expects(self::once())->method('flush');

        $recorder = new AiInteractionRecorder($entityManager);

        $user = new User('admin@example.com', 'Admin', 'User');
        $workspace = new Workspace($user, "Admin's Workspace");
        $assistant = new Assistant($workspace, 'Nova', 'female', 'data:image/png;base64,abc');
        $conversation = new Conversation($assistant, 'Check tokens', 'openai', 'gpt-5');

        $prompt = new TextPrompt([
            new TextPromptMessage(TextPromptRole::USER, 'Summarise service status.'),
        ]);

        $result = new TextResult('openai', 'All good', [
            'model' => 'gpt-5',
            'usage' => [
                'prompt_tokens' => 24,
                'completion_tokens' => 12,
            ],
        ]);

        $recorder->recordSuccess($prompt, $result, 'openai', $assistant, $conversation);

        self::assertNotNull($capturedInteraction);
        self::assertSame(AiInteractionStatus::SUCCESS, $capturedInteraction->getStatus());
        self::assertSame('Summarise service status.', $capturedInteraction->getContext());
        self::assertSame(24, $capturedInteraction->getPromptTokens());
        self::assertSame(12, $capturedInteraction->getCompletionTokens());
        self::assertSame(36, $capturedInteraction->getTotalTokens());
        self::assertSame('openai', $capturedInteraction->getProviderName());
        self::assertSame('gpt-5', $capturedInteraction->getModel());

        $metadata = $capturedInteraction->getMetadata();
        self::assertSame('Summarise service status.', $metadata['prompt']['details']['messages'][0]['content']);
        self::assertSame('All good', $metadata['result']['content']);
    }

    public function testRecordFailureCapturesErrorDetails(): void
    {
        $capturedInteraction = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(function ($interaction) use (&$capturedInteraction) {
                $capturedInteraction = $interaction;

                return $interaction instanceof \App\Entity\AiInteraction;
            }));
        $entityManager->expects(self::once())->method('flush');

        $recorder = new AiInteractionRecorder($entityManager);

        $user = new User('admin@example.com', 'Admin', 'User');
        $workspace = new Workspace($user, "Admin's Workspace");
        $assistant = new Assistant($workspace, 'Nova', 'female', 'data:image/png;base64,abc');
        $conversation = new Conversation($assistant, 'Check failures', 'openai', 'gpt-5');

        $prompt = new TextPrompt([
            new TextPromptMessage(TextPromptRole::USER, 'Trigger failure'),
        ]);

        $exception = new \RuntimeException('Provider unavailable');

        $recorder->recordFailure($prompt, 'openai', $exception, $assistant, $conversation);

        self::assertNotNull($capturedInteraction);
        self::assertSame(AiInteractionStatus::FAILURE, $capturedInteraction->getStatus());
        self::assertSame('RuntimeException', $capturedInteraction->getErrorCode());
        self::assertSame('Provider unavailable', $capturedInteraction->getErrorMessage());
        self::assertSame('Trigger failure', $capturedInteraction->getContext());
        self::assertNull($capturedInteraction->getPromptTokens());
        self::assertNull($capturedInteraction->getCostCents());

        $metadata = $capturedInteraction->getMetadata();
        self::assertSame('RuntimeException', $metadata['exception']['class']);
        self::assertNotEmpty($metadata['exception']['trace']);
    }
}
