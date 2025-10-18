<?php

namespace App\Repository;

use App\ArtificialIntelligence\Interaction\AiInteractionStatus;
use App\Entity\AiInteraction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiInteraction>
 */
class AiInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiInteraction::class);
    }

    /**
     * @return list<AiInteraction>
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('interaction')
            ->orderBy('interaction.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AiInteraction>
     */
    public function findRecentFailures(int $limit = 50): array
    {
        return $this->createQueryBuilder('interaction')
            ->andWhere('interaction.status = :status')
            ->setParameter('status', AiInteractionStatus::FAILURE)
            ->orderBy('interaction.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AiInteraction>
     */
    public function findFailuresForConversations(array $conversationIds, int $limit = 100): array
    {
        if ($conversationIds === []) {
            return [];
        }

        return $this->createQueryBuilder('interaction')
            ->andWhere('interaction.conversation IN (:conversationIds)')
            ->andWhere('interaction.status = :status')
            ->setParameter('conversationIds', $conversationIds)
            ->setParameter('status', AiInteractionStatus::FAILURE)
            ->orderBy('interaction.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<string> $conversationIds
     *
     * @return array<string, array{
     *     interactionCount: int,
     *     failureCount: int,
     *     promptTokens: int,
     *     completionTokens: int,
     *     totalTokens: int,
     *     costCents: int
     * }>
     */
    public function getConversationMetrics(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('interaction')
            ->select([
                'IDENTITY(interaction.conversation) AS conversationId',
                'COUNT(interaction.id) AS interactionCount',
                'SUM(COALESCE(interaction.promptTokens, 0)) AS promptTokens',
                'SUM(COALESCE(interaction.completionTokens, 0)) AS completionTokens',
                'SUM(COALESCE(interaction.totalTokens, 0)) AS totalTokens',
                'SUM(COALESCE(interaction.costCents, 0)) AS costCents',
                'SUM(CASE WHEN interaction.status = :failure THEN 1 ELSE 0 END) AS failureCount',
            ])
            ->andWhere('interaction.conversation IN (:conversationIds)')
            ->setParameter('conversationIds', $conversationIds)
            ->setParameter('failure', AiInteractionStatus::FAILURE)
            ->groupBy('interaction.conversation')
            ->getQuery()
            ->getArrayResult();

        $metrics = [];

        foreach ($rows as $row) {
            $conversationId = (string) $row['conversationId'];

            $metrics[$conversationId] = [
                'interactionCount' => (int) $row['interactionCount'],
                'failureCount' => (int) $row['failureCount'],
                'promptTokens' => (int) $row['promptTokens'],
                'completionTokens' => (int) $row['completionTokens'],
                'totalTokens' => (int) $row['totalTokens'],
                'costCents' => (int) $row['costCents'],
            ];
        }

        return $metrics;
    }

    /**
     * @return array{
     *     totalInteractions: int,
     *     totalFailures: int,
     *     promptTokens: int,
     *     completionTokens: int,
     *     totalTokens: int,
     *     costCents: int
     * }
     */
    public function getSummaryMetrics(): array
    {
        $row = $this->createQueryBuilder('interaction')
            ->select([
                'COUNT(interaction.id) AS totalInteractions',
                'SUM(CASE WHEN interaction.status = :failure THEN 1 ELSE 0 END) AS totalFailures',
                'SUM(COALESCE(interaction.promptTokens, 0)) AS promptTokens',
                'SUM(COALESCE(interaction.completionTokens, 0)) AS completionTokens',
                'SUM(COALESCE(interaction.totalTokens, 0)) AS totalTokens',
                'SUM(COALESCE(interaction.costCents, 0)) AS costCents',
            ])
            ->setParameter('failure', AiInteractionStatus::FAILURE)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalInteractions' => isset($row['totalInteractions']) ? (int) $row['totalInteractions'] : 0,
            'totalFailures' => isset($row['totalFailures']) ? (int) $row['totalFailures'] : 0,
            'promptTokens' => isset($row['promptTokens']) ? (int) $row['promptTokens'] : 0,
            'completionTokens' => isset($row['completionTokens']) ? (int) $row['completionTokens'] : 0,
            'totalTokens' => isset($row['totalTokens']) ? (int) $row['totalTokens'] : 0,
            'costCents' => isset($row['costCents']) ? (int) $row['costCents'] : 0,
        ];
    }
}

