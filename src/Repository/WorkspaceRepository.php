<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workspace>
 */
class WorkspaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workspace::class);
    }

    /**
     * @return array<int, array{id: string, name: string, role: string}>
     */
    public function findSummariesForUser(User $user): array
    {
        return $this->createQueryBuilder('workspace')
            ->select('workspace.id AS id', 'workspace.name AS name', 'membership.role AS role')
            ->innerJoin('App\Entity\WorkspaceMembership', 'membership', 'WITH', 'membership.workspace = workspace')
            ->andWhere('membership.user = :user')
            ->setParameter('user', $user)
            ->orderBy('workspace.name', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
