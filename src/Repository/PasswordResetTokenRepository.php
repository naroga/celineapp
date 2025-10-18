<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function save(PasswordResetToken $token, bool $flush = false): void
    {
        $this->getEntityManager()->persist($token);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeAllActiveForUser(User $user): void
    {
        $this->createQueryBuilder('token')
            ->delete()
            ->where('token.user = :user')
            ->andWhere('token.consumedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function findActiveBySelector(string $selector, DateTimeImmutable $now): ?PasswordResetToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.selector = :selector')
            ->andWhere('token.consumedAt IS NULL')
            ->andWhere('token.expiresAt > :now')
            ->setParameter('selector', $selector)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
