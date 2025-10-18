<?php

namespace App\Entity;

use App\ArtificialIntelligence\Conversation\ConversationRole;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\Repository\ConversationTurnRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: ConversationTurnRepository::class)]
#[ORM\Table(name: 'conversation_turns')]
#[ORM\Index(columns: ['conversation_id', 'created_at'], name: 'idx_conversation_turns_conversation_created_at')]
class ConversationTurn
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'turns')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\Column(enumType: ConversationRole::class)]
    private ConversationRole $role;

    #[ORM\Column(enumType: PromptType::class)]
    private PromptType $promptType;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $providerName;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $model;

    #[ORM\Column(type: 'json')]
    private array $content;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $promptTokens;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $completionTokens;

    #[ORM\Column(type: 'json')]
    private array $metadata;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        Conversation $conversation,
        ConversationRole $role,
        PromptType $promptType,
        array $content,
        ?string $providerName = null,
        ?string $model = null,
        ?int $promptTokens = null,
        ?int $completionTokens = null,
        array $metadata = [],
    ) {
        if ($providerName !== null && trim($providerName) === '') {
            throw new \InvalidArgumentException('Provider name must be a non-empty string or null.');
        }

        if ($model !== null && trim($model) === '') {
            throw new \InvalidArgumentException('Model must be a non-empty string or null.');
        }

        if ($promptTokens !== null && $promptTokens < 0) {
            throw new \InvalidArgumentException('Prompt tokens must be zero or a positive integer.');
        }

        if ($completionTokens !== null && $completionTokens < 0) {
            throw new \InvalidArgumentException('Completion tokens must be zero or a positive integer.');
        }

        $this->id = Uuid::uuid4()->toString();
        $this->conversation = $conversation;
        $this->role = $role;
        $this->promptType = $promptType;
        $this->content = $content;
        $this->providerName = $providerName;
        $this->model = $model;
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->metadata = $metadata;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function getRole(): ConversationRole
    {
        return $this->role;
    }

    public function getPromptType(): PromptType
    {
        return $this->promptType;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
