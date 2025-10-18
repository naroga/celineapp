<?php

namespace App\Service\Admin;

use App\ArtificialIntelligence\Interaction\AiInteractionStatus;
use App\Entity\AiInteraction;
use App\Entity\Conversation;
use App\Entity\ConversationTurn;
use App\Repository\AiInteractionRepository;
use App\Repository\ConversationRepository;
use App\Repository\ConversationTurnRepository;

final class AiAdminDashboardBuilder
{
    public function __construct(
        private readonly AiInteractionRepository $interactionRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationTurnRepository $turnRepository,
    ) {
    }

    /**
     * @return array{
     *     summary: array<string, int>,
     *     recentInteractions: list<array<string, mixed>>,
     *     recentFailures: list<array<string, mixed>>,
     *     conversations: list<array<string, mixed>>
     * }
     */
    public function buildDashboard(int $interactionLimit = 50, int $conversationLimit = 20): array
    {
        $recentInteractions = $this->interactionRepository->findRecent($interactionLimit);
        $recentFailures = $this->interactionRepository->findRecentFailures(20);
        $recentConversations = $this->conversationRepository->findMostRecent($conversationLimit);

        $conversationMetrics = $this->interactionRepository->getConversationMetrics(
            array_map(static fn (Conversation $conversation): string => $conversation->getId(), $recentConversations),
        );

        return [
            'summary' => $this->buildSummary(),
            'recentInteractions' => array_map([$this, 'mapInteraction'], $recentInteractions),
            'recentFailures' => array_map([$this, 'mapInteraction'], $recentFailures),
            'conversations' => array_map(
                fn (Conversation $conversation) => $this->mapConversation(
                    $conversation,
                    $conversationMetrics[$conversation->getId()] ?? null,
                ),
                $recentConversations,
            ),
        ];
    }

    public function buildInteractionDetail(AiInteraction $interaction): array
    {
        $conversation = $interaction->getConversation();

        return [
            'interaction' => $this->mapInteraction($interaction),
            'metadata' => $interaction->getMetadata(),
            'tools' => $this->extractToolExecutions($interaction->getMetadata()),
            'conversation' => $conversation === null ? null : [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'assistant' => [
                    'id' => $conversation->getAssistant()->getId(),
                    'name' => $conversation->getAssistant()->getName(),
                ],
                'workspace' => [
                    'id' => $conversation->getWorkspace()->getId(),
                    'name' => $conversation->getWorkspace()->getName(),
                ],
                'provider' => $conversation->getProviderName(),
                'model' => $conversation->getModel(),
                'createdAt' => $conversation->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $conversation->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                'closedAt' => $conversation->getClosedAt()?->format(\DateTimeInterface::ATOM),
                'turns' => array_map([$this, 'mapConversationTurn'], $this->turnRepository->findChronologicalForConversation($conversation)),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function buildSummary(): array
    {
        $summary = $this->interactionRepository->getSummaryMetrics();

        return [
            'totalInteractions' => $summary['totalInteractions'],
            'totalFailures' => $summary['totalFailures'],
            'promptTokens' => $summary['promptTokens'],
            'completionTokens' => $summary['completionTokens'],
            'totalTokens' => $summary['totalTokens'],
            'costCents' => $summary['costCents'],
            'failureRate' => $summary['totalInteractions'] > 0
                ? (int) round(($summary['totalFailures'] / $summary['totalInteractions']) * 100)
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapInteraction(AiInteraction $interaction): array
    {
        $assistant = $interaction->getAssistant();
        $workspace = $interaction->getWorkspace();
        $conversation = $interaction->getConversation();
        $toolExecutions = $this->extractToolExecutions($interaction->getMetadata());

        return [
            'id' => $interaction->getId(),
            'createdAt' => $interaction->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'status' => $interaction->getStatus()->value,
            'promptType' => $interaction->getPromptType()->value,
            'provider' => $interaction->getProviderName(),
            'model' => $interaction->getModel(),
            'context' => $interaction->getContext(),
            'tokens' => [
                'prompt' => $interaction->getPromptTokens(),
                'completion' => $interaction->getCompletionTokens(),
                'total' => $interaction->getTotalTokens(),
            ],
            'costCents' => $interaction->getCostCents(),
            'error' => $interaction->getStatus() === AiInteractionStatus::FAILURE ? [
                'code' => $interaction->getErrorCode(),
                'message' => $interaction->getErrorMessage(),
            ] : null,
            'assistant' => $assistant === null ? null : [
                'id' => $assistant->getId(),
                'name' => $assistant->getName(),
            ],
            'workspace' => $workspace === null ? null : [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
            ],
            'conversation' => $conversation === null ? null : [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
            ],
            'toolExecutionCount' => count($toolExecutions),
        ];
    }

    /**
     * @param array<string, int>|null $metrics
     *
     * @return array<string, mixed>
     */
    private function mapConversation(Conversation $conversation, ?array $metrics): array
    {
        $assistant = $conversation->getAssistant();
        $workspace = $conversation->getWorkspace();

        $metrics ??= [
            'interactionCount' => 0,
            'failureCount' => 0,
            'promptTokens' => 0,
            'completionTokens' => 0,
            'totalTokens' => 0,
            'costCents' => 0,
        ];

        return [
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'assistant' => [
                'id' => $assistant->getId(),
                'name' => $assistant->getName(),
            ],
            'workspace' => [
                'id' => $workspace->getId(),
                'name' => $workspace->getName(),
            ],
            'provider' => $conversation->getProviderName(),
            'model' => $conversation->getModel(),
            'createdAt' => $conversation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $conversation->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'closedAt' => $conversation->getClosedAt()?->format(\DateTimeInterface::ATOM),
            'metrics' => [
                'interactionCount' => $metrics['interactionCount'],
                'failureCount' => $metrics['failureCount'],
                'promptTokens' => $metrics['promptTokens'],
                'completionTokens' => $metrics['completionTokens'],
                'totalTokens' => $metrics['totalTokens'],
                'costCents' => $metrics['costCents'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapConversationTurn(ConversationTurn $turn): array
    {
        return [
            'id' => $turn->getId(),
            'role' => $turn->getRole()->value,
            'promptType' => $turn->getPromptType()->value,
            'provider' => $turn->getProviderName(),
            'model' => $turn->getModel(),
            'content' => $turn->getContent(),
            'tokens' => [
                'prompt' => $turn->getPromptTokens(),
                'completion' => $turn->getCompletionTokens(),
            ],
            'metadata' => $turn->getMetadata(),
            'createdAt' => $turn->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return list<array<string, mixed>>
     */
    private function extractToolExecutions(array $metadata): array
    {
        $overrides = [];

        if (isset($metadata['overrides']) && is_array($metadata['overrides'])) {
            $overrides = $metadata['overrides'];
        }

        $executions = [];

        if (isset($overrides['toolExecutions']) && is_array($overrides['toolExecutions'])) {
            $executions = $overrides['toolExecutions'];
        } elseif (isset($overrides['tools']) && is_array($overrides['tools'])) {
            $executions = $overrides['tools'];
        }

        if (!is_array($executions) || $executions === []) {
            return [];
        }

        return array_values(array_map(
            static function (array $execution): array {
                $arguments = $execution['arguments'] ?? [];

                if (!is_array($arguments)) {
                    $arguments = [];
                }

                $result = $execution['result'] ?? null;

                if (!is_array($result)) {
                    $result = null;
                } else {
                    $result = [
                        'content' => $result['content'] ?? null,
                        'metadata' => isset($result['metadata']) && is_array($result['metadata']) ? $result['metadata'] : [],
                    ];
                }

                $error = $execution['error'] ?? null;

                if (is_array($error)) {
                    $error = [
                        'class' => $error['class'] ?? null,
                        'message' => $error['message'] ?? null,
                        'trace' => $error['trace'] ?? null,
                    ];
                } else {
                    $error = null;
                }

                return [
                    'iteration' => $execution['iteration'] ?? null,
                    'callId' => $execution['call_id'] ?? null,
                    'tool' => $execution['tool'] ?? null,
                    'arguments' => $arguments,
                    'status' => $execution['status'] ?? 'success',
                    'durationMs' => $execution['duration_ms'] ?? null,
                    'timestamp' => $execution['timestamp'] ?? null,
                    'stackTrace' => $execution['stack_trace'] ?? null,
                    'result' => $result,
                    'error' => $error,
                ];
            },
            array_filter(
                $executions,
                static fn ($entry): bool => is_array($entry),
            ),
        ));
    }
}
