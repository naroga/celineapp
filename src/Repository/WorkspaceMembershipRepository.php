<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceMembership;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkspaceMembership>
 */
class WorkspaceMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkspaceMembership::class);
    }

    public function save(WorkspaceMembership $membership, bool $flush = false): void
    {
        $this->getEntityManager()->persist($membership);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByUserAndWorkspace(User $user, Workspace $workspace): ?WorkspaceMembership
    {
        return $this->createQueryBuilder('membership')
            ->andWhere('membership.user = :user')
            ->andWhere('membership.workspace = :workspace')
            ->setParameter('user', $user)
            ->setParameter('workspace', $workspace)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return WorkspaceMembership[]
     */
    public function findMembersForWorkspace(Workspace $workspace): array
    {
        return $this->createQueryBuilder('membership')
            ->addSelect('user')
            ->innerJoin('membership.user', 'user')
            ->andWhere('membership.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('membership.role', 'ASC')
            ->addOrderBy('user.firstName', 'ASC')
            ->addOrderBy('user.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
