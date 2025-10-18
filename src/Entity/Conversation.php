<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversations')]
class Conversation
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Assistant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Assistant $assistant;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workspace $workspace;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $providerName = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $closedAt = null;

    /**
     * @var Collection<int, ConversationTurn>
     */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: ConversationTurn::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $turns;

    public function __construct(Assistant $assistant, ?string $title = null, ?string $providerName = null, ?string $model = null)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->assistant = $assistant;
        $this->workspace = $assistant->getWorkspace();
        $this->title = $title;
        $this->providerName = $providerName;
        $this->model = $model;
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->metadata = [];
        $this->turns = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAssistant(): Assistant
    {
        return $this->assistant;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function rename(?string $title): void
    {
        $this->title = $title;
        $this->touch();
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(?string $providerName): void
    {
        $this->providerName = $providerName;
        $this->touch();
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): void
    {
        $this->model = $model;
        $this->touch();
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
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

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function close(DateTimeImmutable $closedAt): void
    {
        $this->closedAt = $closedAt;
        $this->touch();
    }

    /**
     * @return Collection<int, ConversationTurn>
     */
    public function getTurns(): Collection
    {
        return $this->turns;
    }

    /**
     * @internal Used by Doctrine only.
     */
    public function addTurn(ConversationTurn $turn): void
    {
        if (!$this->turns->contains($turn)) {
            $this->turns->add($turn);
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
