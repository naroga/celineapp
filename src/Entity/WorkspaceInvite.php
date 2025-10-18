<?php

namespace App\Entity;

use App\Repository\WorkspaceInviteRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: WorkspaceInviteRepository::class)]
#[ORM\Table(name: 'workspace_invites')]
#[ORM\UniqueConstraint(name: 'uniq_workspace_invite_token', fields: ['token'])]
class WorkspaceInvite
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $invitedBy;

    #[ORM\Column(length: 191)]
    private string $email;

    #[ORM\Column(length: 120)]
    private string $token;

    #[ORM\Column(length: 32)]
    private string $status;

    #[ORM\Column(length: 32)]
    private string $role;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $invitedUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $respondedBy = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $respondedAt = null;

    public function __construct(
        Workspace $workspace,
        User $invitedBy,
        string $email,
        string $role,
        int $ttlInSeconds
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->workspace = $workspace;
        $this->invitedBy = $invitedBy;
        $this->email = mb_strtolower($email);
        $this->role = $this->assertRole($role);
        $this->status = self::STATUS_PENDING;
        $this->token = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->expiresAt = $now->add(new DateInterval(sprintf('PT%dS', $ttlInSeconds)));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function getInvitedBy(): User
    {
        return $this->invitedBy;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getInvitedUser(): ?User
    {
        return $this->invitedUser;
    }

    public function assignInvitedUser(User $user): void
    {
        if ($this->invitedUser !== null && $this->invitedUser->getId() !== $user->getId()) {
            throw new \InvalidArgumentException('Invite already linked to another user.');
        }

        $this->invitedUser = $user;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getRespondedBy(): ?User
    {
        return $this->respondedBy;
    }

    public function getRespondedAt(): ?DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function markAccepted(User $user, DateTimeImmutable $acceptedAt): void
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->respondedAt = $acceptedAt;
        $this->respondedBy = $user;
        $this->assignInvitedUser($user);
    }

    public function markDeclined(User $user, DateTimeImmutable $declinedAt): void
    {
        $this->status = self::STATUS_DECLINED;
        $this->respondedAt = $declinedAt;
        $this->respondedBy = $user;
    }

    public function markExpired(DateTimeImmutable $expiredAt): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->respondedAt = $expiredAt;
        $this->respondedBy = null;
    }

    public function isPending(DateTimeImmutable $now): bool
    {
        return $this->status === self::STATUS_PENDING && $this->expiresAt > $now;
    }

    public function hasExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }

    private function assertRole(string $role): string
    {
        $role = mb_strtolower($role);

        if (!in_array($role, WorkspaceMembership::availableRoles(), true)) {
            throw new \InvalidArgumentException(sprintf('Invalid workspace role "%s".', $role));
        }

        return $role;
    }
}
