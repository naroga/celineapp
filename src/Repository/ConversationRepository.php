<?php

namespace App\Repository;

use App\Entity\Assistant;
use App\Entity\Conversation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * @return list<Conversation>
     */
    public function findActiveByAssistant(Assistant $assistant, int $limit = 50): array
    {
        return $this->createQueryBuilder('conversation')
            ->andWhere('conversation.assistant = :assistant')
            ->andWhere('conversation.closedAt IS NULL')
            ->setParameter('assistant', $assistant)
            ->orderBy('conversation.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Conversation>
     */
    public function findMostRecent(int $limit = 25): array
    {
        return $this->createQueryBuilder('conversation')
            ->orderBy('conversation.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
