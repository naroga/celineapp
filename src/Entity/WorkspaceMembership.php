<?php

namespace App\Entity;

use App\Repository\WorkspaceMembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: WorkspaceMembershipRepository::class)]
#[ORM\Table(name: 'workspace_memberships')]
#[ORM\UniqueConstraint(name: 'uniq_workspace_membership', columns: ['workspace_id', 'user_id'])]
class WorkspaceMembership
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 32)]
    private string $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $joinedAt;

    public function __construct(Workspace $workspace, User $user, string $role)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->workspace = $workspace;
        $this->user = $user;
        $this->role = self::assertValidRole($role);
        $this->joinedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function promote(string $role): void
    {
        $this->role = self::assertValidRole($role);
    }

    public function getJoinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }

    /**
     * @return string[]
     */
    public static function availableRoles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_MEMBER,
        ];
    }

    private static function assertValidRole(string $role): string
    {
        $role = mb_strtolower($role);

        if (!in_array($role, self::availableRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Invalid workspace role "%s".', $role));
        }

        return $role;
    }
}
