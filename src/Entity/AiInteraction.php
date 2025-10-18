<?php

namespace App\Entity;

use App\ArtificialIntelligence\Interaction\AiInteractionStatus;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\Repository\AiInteractionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: AiInteractionRepository::class)]
#[ORM\Table(name: 'ai_interactions')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_ai_interactions_status_created_at')]
#[ORM\Index(columns: ['conversation_id', 'created_at'], name: 'idx_ai_interactions_conversation_created_at')]
#[ORM\Index(columns: ['context'], name: 'idx_ai_interactions_context')]
class AiInteraction
{
    #[ORM\Id]
    #[ORM\Column(length: 36)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class)]
    #[ORM\JoinColumn(name: 'workspace_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Assistant::class)]
    #[ORM\JoinColumn(name: 'assistant_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Assistant $assistant;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Conversation $conversation;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $context;

    #[ORM\Column(enumType: PromptType::class)]
    private PromptType $promptType;

    #[ORM\Column(length: 80)]
    private string $providerName;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $model;

    #[ORM\Column(enumType: AiInteractionStatus::class)]
    private AiInteractionStatus $status;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $errorCode;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $promptTokens;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $completionTokens;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalTokens;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $costCents;

    #[ORM\Column(type: 'json')]
    private array $metadata;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        ?Workspace $workspace,
        ?Assistant $assistant,
        ?Conversation $conversation,
        ?string $context,
        PromptType $promptType,
        string $providerName,
        ?string $model,
        AiInteractionStatus $status,
        ?int $promptTokens = null,
        ?int $completionTokens = null,
        ?int $totalTokens = null,
        ?int $costCents = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        array $metadata = [],
    ) {
        $trimmedProvider = trim($providerName);

        if ($trimmedProvider === '') {
            throw new \InvalidArgumentException('Provider name is required for AI interactions.');
        }

        if ($context !== null) {
            $context = trim($context);
            if ($context === '') {
                $context = null;
            }
        }

        if ($promptTokens !== null && $promptTokens < 0) {
            throw new \InvalidArgumentException('Prompt tokens must be zero or a positive integer.');
        }

        if ($completionTokens !== null && $completionTokens < 0) {
            throw new \InvalidArgumentException('Completion tokens must be zero or a positive integer.');
        }

        if ($totalTokens !== null && $totalTokens < 0) {
            throw new \InvalidArgumentException('Total tokens must be zero or a positive integer.');
        }

        if ($costCents !== null && $costCents < 0) {
            throw new \InvalidArgumentException('Cost (in cents) must be zero or a positive integer.');
        }

        $this->id = Uuid::uuid4()->toString();
        $this->workspace = $workspace;
        $this->assistant = $assistant;
        $this->conversation = $conversation;
        $this->context = $context;
        $this->promptType = $promptType;
        $this->providerName = $trimmedProvider;
        $this->model = $model !== null && trim($model) !== '' ? $model : null;
        $this->status = $status;
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->totalTokens = $totalTokens;
        $this->costCents = $costCents;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->metadata = $metadata;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
    }

    public function getAssistant(): ?Assistant
    {
        return $this->assistant;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getPromptType(): PromptType
    {
        return $this->promptType;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getStatus(): AiInteractionStatus
    {
        return $this->status;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    public function getCostCents(): ?int
    {
        return $this->costCents;
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

