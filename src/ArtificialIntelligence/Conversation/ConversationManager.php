<?php

namespace App\ArtificialIntelligence\Conversation;

use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\Entity\Assistant;
use App\Entity\Conversation;
use App\Entity\ConversationTurn;
use App\Repository\ConversationRepository;
use App\Repository\ConversationTurnRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ConversationManager
{
    private const DEFAULT_MAX_TURNS = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationTurnRepository $turnRepository,
    ) {
    }

    public function startConversation(
        Assistant $assistant,
        ?string $title = null,
        ?string $providerName = null,
        ?string $model = null,
        bool $includePlaybook = true,
    ): Conversation {
        $conversation = new Conversation(
            $assistant,
            $title,
            $providerName ?? $assistant->getDefaultProvider(),
            $model ?? $assistant->getDefaultModel(),
        );

        $this->entityManager->persist($conversation);

        if ($includePlaybook) {
            $playbook = trim((string) ($assistant->getPlaybook() ?? ''));

            if ($playbook !== '') {
                $turn = new ConversationTurn(
                    $conversation,
                    ConversationRole::SYSTEM,
                    PromptType::TEXT,
                    ['text' => $playbook],
                );
                $conversation->addTurn($turn);
                $this->entityManager->persist($turn);
            }
        }

        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $metadata
     */
    public function addTurn(
        Conversation $conversation,
        ConversationRole $role,
        PromptType $promptType,
        array $content,
        ?string $providerName = null,
        ?string $model = null,
        ?int $promptTokens = null,
        ?int $completionTokens = null,
        array $metadata = [],
        bool $flush = true,
    ): ConversationTurn {
        $turn = new ConversationTurn(
            $conversation,
            $role,
            $promptType,
            $content,
            $providerName,
            $model,
            $promptTokens,
            $completionTokens,
            $metadata,
        );

        $conversation->addTurn($turn);
        $this->entityManager->persist($turn);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $turn;
    }

    /**
     * @return list<ConversationTurn>
     */
    public function getContextTurns(
        Conversation $conversation,
        ?int $maxTokens = null,
        int $maxTurns = self::DEFAULT_MAX_TURNS,
    ): array {
        $recentTurns = $this->turnRepository->findRecentForConversation($conversation, $maxTurns * 2);

        if ($recentTurns === []) {
            return [];
        }

        $window = [];
        $tokenCount = 0;

        foreach ($recentTurns as $turn) {
            $turnTokens = ($turn->getPromptTokens() ?? 0) + ($turn->getCompletionTokens() ?? 0);

            if ($window !== [] && $maxTokens !== null && $tokenCount + $turnTokens > $maxTokens) {
                break;
            }

            $window[] = $turn;
            $tokenCount += $turnTokens;

            if (count($window) >= $maxTurns) {
                break;
            }
        }

        return array_reverse($window);
    }

    /**
     * @return list<TextPromptMessage>
     */
    public function buildTextPromptContext(
        Conversation $conversation,
        ?int $maxTokens = null,
        int $maxTurns = self::DEFAULT_MAX_TURNS,
    ): array {
        $messages = [];

        foreach ($this->getContextTurns($conversation, $maxTokens, $maxTurns) as $turn) {
            if ($turn->getPromptType() !== PromptType::TEXT) {
                continue;
            }

            $content = $turn->getContent()['text'] ?? null;

            if (!is_string($content) || $content === '') {
                continue;
            }

            $messages[] = new TextPromptMessage(
                $this->mapRoleToTextPromptRole($turn->getRole()),
                $content,
            );
        }

        return $messages;
    }

    private function mapRoleToTextPromptRole(ConversationRole $role): TextPromptRole
    {
        return match ($role) {
            ConversationRole::SYSTEM => TextPromptRole::SYSTEM,
            ConversationRole::USER => TextPromptRole::USER,
            ConversationRole::ASSISTANT => TextPromptRole::ASSISTANT,
        };
    }
}
