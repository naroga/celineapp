<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\ConversationTurn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConversationTurn>
 */
class ConversationTurnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConversationTurn::class);
    }

    /**
     * @return list<ConversationTurn>
     */
    public function findRecentForConversation(Conversation $conversation, int $limit = 50): array
    {
        return $this->createQueryBuilder('turn')
            ->andWhere('turn.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('turn.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ConversationTurn>
     */
    public function findChronologicalForConversation(Conversation $conversation, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('turn')
            ->andWhere('turn.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('turn.createdAt', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
