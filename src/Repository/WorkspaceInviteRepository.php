<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvite;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkspaceInvite>
 */
class WorkspaceInviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkspaceInvite::class);
    }

    public function save(WorkspaceInvite $invite, bool $flush = false): void
    {
        $this->getEntityManager()->persist($invite);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return WorkspaceInvite[]
     */
    public function findPendingForWorkspaceAndEmail(Workspace $workspace, string $email): array
    {
        return $this->createQueryBuilder('invite')
            ->andWhere('invite.workspace = :workspace')
            ->andWhere('invite.email = :email')
            ->andWhere('invite.status = :status')
            ->setParameter('workspace', $workspace)
            ->setParameter('email', mb_strtolower($email))
            ->setParameter('status', WorkspaceInvite::STATUS_PENDING)
            ->getQuery()
            ->getResult();
    }

    public function findOnePendingByToken(string $token, DateTimeImmutable $now): ?WorkspaceInvite
    {
        return $this->createQueryBuilder('invite')
            ->andWhere('invite.token = :token')
            ->andWhere('invite.status = :status')
            ->andWhere('invite.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('status', WorkspaceInvite::STATUS_PENDING)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return WorkspaceInvite[]
     */
    public function findPendingForUser(User $user, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('invite')
            ->addSelect('workspace')
            ->addSelect('inviter')
            ->innerJoin('invite.workspace', 'workspace')
            ->innerJoin('invite.invitedBy', 'inviter')
            ->andWhere('invite.status = :status')
            ->andWhere('invite.expiresAt > :now')
            ->andWhere('invite.invitedUser = :user OR invite.email = :email')
            ->setParameter('status', WorkspaceInvite::STATUS_PENDING)
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->setParameter('email', mb_strtolower($user->getEmail()))
            ->orderBy('invite.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return WorkspaceInvite[]
     */
    public function findClaimableForEmail(string $email, DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('invite')
            ->andWhere('invite.email = :email')
            ->andWhere('invite.status = :status')
            ->andWhere('invite.expiresAt > :now')
            ->setParameter('email', mb_strtolower($email))
            ->setParameter('status', WorkspaceInvite::STATUS_PENDING)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function findOneById(string $id): ?WorkspaceInvite
    {
        return $this->createQueryBuilder('invite')
            ->addSelect('workspace', 'inviter')
            ->innerJoin('invite.workspace', 'workspace')
            ->innerJoin('invite.invitedBy', 'inviter')
            ->andWhere('invite.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return WorkspaceInvite[]
     */
    public function findForWorkspace(Workspace $workspace): array
    {
        return $this->createQueryBuilder('invite')
            ->addSelect('inviter', 'invitedUser')
            ->innerJoin('invite.invitedBy', 'inviter')
            ->leftJoin('invite.invitedUser', 'invitedUser')
            ->andWhere('invite.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('invite.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
