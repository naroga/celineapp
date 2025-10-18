<?php

namespace App\Tests\ArtificialIntelligence\Conversation;

use App\ArtificialIntelligence\Conversation\ConversationManager;
use App\ArtificialIntelligence\Conversation\ConversationRole;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\Entity\Assistant;
use App\Entity\Conversation;
use App\Entity\ConversationTurn;
use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\ConversationRepository;
use App\Repository\ConversationTurnRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ConversationManagerTest extends TestCase
{
    public function testStartConversationSeedsPlaybook(): void
    {
        $assistant = $this->createAssistant('You are helpful.');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::atLeastOnce())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $conversationRepository = $this->createMock(ConversationRepository::class);
        $turnRepository = $this->createMock(ConversationTurnRepository::class);

        $manager = new ConversationManager($entityManager, $conversationRepository, $turnRepository);

        $conversation = $manager->startConversation($assistant);

        self::assertInstanceOf(Conversation::class, $conversation);
        self::assertCount(1, $conversation->getTurns());

        /** @var ConversationTurn $turn */
        $turn = $conversation->getTurns()->first();
        self::assertSame(ConversationRole::SYSTEM, $turn->getRole());
        self::assertSame(['text' => 'You are helpful.'], $turn->getContent());
    }

    public function testBuildTextPromptContextReturnsOrderedMessages(): void
    {
        $assistant = $this->createAssistant();
        $conversation = new Conversation($assistant);

        $systemTurn = new ConversationTurn(
            $conversation,
            ConversationRole::SYSTEM,
            PromptType::TEXT,
            ['text' => 'Follow the rules.'],
            null,
            null,
            5,
        );
        $conversation->addTurn($systemTurn);

        $userTurn = new ConversationTurn(
            $conversation,
            ConversationRole::USER,
            PromptType::TEXT,
            ['text' => 'Hello!'],
            null,
            null,
            4,
        );
        $conversation->addTurn($userTurn);

        $assistantTurn = new ConversationTurn(
            $conversation,
            ConversationRole::ASSISTANT,
            PromptType::TEXT,
            ['text' => 'Hi there!'],
            'openai',
            'gpt-5',
            3,
            6,
        );
        $conversation->addTurn($assistantTurn);

        $turnRepository = $this->createMock(ConversationTurnRepository::class);
        $turnRepository->method('findRecentForConversation')
            ->with($conversation, self::anything())
            ->willReturn([$assistantTurn, $userTurn, $systemTurn]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $conversationRepository = $this->createMock(ConversationRepository::class);

        $manager = new ConversationManager($entityManager, $conversationRepository, $turnRepository);

        $messages = $manager->buildTextPromptContext($conversation);

        self::assertCount(3, $messages);
        self::assertInstanceOf(TextPromptMessage::class, $messages[0]);
        self::assertSame(TextPromptRole::SYSTEM, $messages[0]->getRole());
        self::assertSame('Follow the rules.', $messages[0]->getContent());
        self::assertSame(TextPromptRole::USER, $messages[1]->getRole());
        self::assertSame(TextPromptRole::ASSISTANT, $messages[2]->getRole());
    }

    private function createAssistant(string $playbook = 'Follow the workspace playbook.'): Assistant
    {
        $user = new User('owner@example.com', 'Owner', 'Example');
        $workspace = new Workspace($user, 'Workspace');

        return new Assistant(
            $workspace,
            'Assistant',
            'female',
            'https://example.com/avatar.png',
            'assistant@example.com',
            '+15555555555',
            $playbook,
        );
    }
}
