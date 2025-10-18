<?php

namespace App\Entity;

use App\Repository\AssistantRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AssistantRepository::class)]
#[ORM\Table(name: 'assistants')]
#[ORM\UniqueConstraint(name: 'uniq_assistants_workspace_email', columns: ['workspace_id', 'email'])]
#[UniqueEntity(fields: ['workspace', 'email'])]
class Assistant
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank]
    #[Assert\Choice(['male', 'female'])]
    private string $gender;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $profilePicture;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $playbook = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $defaultProvider = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $defaultModel = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(
        Workspace $workspace,
        string $name,
        string $gender,
        string $profilePicture,
        ?string $email = null,
        ?string $phoneNumber = null,
        ?string $playbook = null,
    ) {
        $this->id = Uuid::uuid4()->toString();
        $this->workspace = $workspace;
        $this->name = $name;
        $this->gender = self::normalizeGender($gender);
        $this->profilePicture = $profilePicture;
        $this->email = self::normalizeEmail($email);
        $this->phoneNumber = self::normalizeString($phoneNumber);
        $this->playbook = self::normalizeString($playbook);
        $this->defaultProvider = null;
        $this->defaultModel = null;
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function getProfilePicture(): string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(string $profilePicture): void
    {
        $this->profilePicture = $profilePicture;
        $this->touch();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $normalized = self::normalizeEmail($email);

        if ($this->email === $normalized) {
            return;
        }

        $this->email = $normalized;
        $this->touch();
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $normalized = self::normalizeGender($gender);

        if ($this->gender === $normalized) {
            return;
        }

        $this->gender = $normalized;
        $this->touch();
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $normalized = self::normalizeString($phoneNumber);

        if ($this->phoneNumber === $normalized) {
            return;
        }

        $this->phoneNumber = $normalized;
        $this->touch();
    }

    public function getPlaybook(): ?string
    {
        return $this->playbook;
    }

    public function setPlaybook(?string $playbook): void
    {
        $normalized = self::normalizeString($playbook);

        if ($this->playbook === $normalized) {
            return;
        }

        $this->playbook = $normalized;
        $this->touch();
    }

    public function getDefaultProvider(): ?string
    {
        return $this->defaultProvider;
    }

    public function setDefaultProvider(?string $defaultProvider): void
    {
        if ($defaultProvider === null) {
            $this->defaultProvider = null;
        } else {
            $normalized = mb_strtolower(trim($defaultProvider));
            $this->defaultProvider = $normalized === '' ? null : $normalized;
        }

        $this->touch();
    }

    public function getDefaultModel(): ?string
    {
        return $this->defaultModel;
    }

    public function setDefaultModel(?string $defaultModel): void
    {
        if ($defaultModel === null) {
            $this->defaultModel = null;
        } else {
            $normalized = trim($defaultModel);
            $this->defaultModel = $normalized === '' ? null : $normalized;
        }

        $this->touch();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    private static function normalizeGender(string $gender): string
    {
        $normalized = mb_strtolower(trim($gender));

        if (!in_array($normalized, ['male', 'female'], true)) {
            throw new \InvalidArgumentException('Unsupported assistant gender.');
        }

        return $normalized;
    }

    private static function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $trimmed = trim($email);

        if ($trimmed === '') {
            return null;
        }

        return mb_strtolower($trimmed);
    }

    private static function normalizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
