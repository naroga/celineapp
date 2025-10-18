<?php

namespace App\Repository;

use App\Entity\Assistant;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assistant>
 */
class AssistantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assistant::class);
    }

    /**
     * @return Assistant[]
     */
    public function findForWorkspace(Workspace $workspace): array
    {
        return $this->createQueryBuilder('assistant')
            ->andWhere('assistant.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('assistant.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForWorkspace(Workspace $workspace, string $assistantId): ?Assistant
    {
        return $this->createQueryBuilder('assistant')
            ->andWhere('assistant.workspace = :workspace')
            ->andWhere('assistant.id = :assistantId')
            ->setParameter('workspace', $workspace)
            ->setParameter('assistantId', $assistantId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
