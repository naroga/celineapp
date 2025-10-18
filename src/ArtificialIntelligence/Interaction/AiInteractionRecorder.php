<?php

namespace App\ArtificialIntelligence\Interaction;

use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\ResultInterface;
use App\ArtificialIntelligence\Tool\ToolDefinition;
use App\Entity\AiInteraction;
use App\Entity\Assistant;
use App\Entity\Conversation;
use App\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;

final class AiInteractionRecorder
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function recordSuccess(
        PromptInterface $prompt,
        ResultInterface $result,
        string $providerName,
        ?Assistant $assistant = null,
        ?Conversation $conversation = null,
        ?Workspace $workspace = null,
        array $metadataOverrides = [],
    ): void {
        $workspace ??= $this->resolveWorkspace($assistant, $conversation);

        $metadata = $this->buildMetadata($prompt, $result, null, $metadataOverrides);

        $usage = $this->extractUsage($result->getMetadata(), $metadataOverrides);

        $interaction = new AiInteraction(
            workspace: $workspace,
            assistant: $assistant,
            conversation: $conversation,
            context: $this->summarisePrompt($prompt),
            promptType: $prompt->getType(),
            providerName: $providerName,
            model: $this->extractModel($result->getMetadata(), $prompt),
            status: AiInteractionStatus::SUCCESS,
            promptTokens: $usage['prompt_tokens'],
            completionTokens: $usage['completion_tokens'],
            totalTokens: $usage['total_tokens'],
            costCents: $usage['cost_cents'],
            errorCode: null,
            errorMessage: null,
            metadata: $metadata,
        );

        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
    }

    public function recordFailure(
        PromptInterface $prompt,
        string $providerName,
        \Throwable $exception,
        ?Assistant $assistant = null,
        ?Conversation $conversation = null,
        ?Workspace $workspace = null,
        array $metadataOverrides = [],
    ): void {
        $workspace ??= $this->resolveWorkspace($assistant, $conversation);

        $metadata = $this->buildMetadata($prompt, null, $exception, $metadataOverrides);

        $interaction = new AiInteraction(
            workspace: $workspace,
            assistant: $assistant,
            conversation: $conversation,
            context: $this->summarisePrompt($prompt),
            promptType: $prompt->getType(),
            providerName: $providerName,
            model: $this->extractModel($metadataOverrides, $prompt),
            status: AiInteractionStatus::FAILURE,
            promptTokens: null,
            completionTokens: null,
            totalTokens: null,
            costCents: null,
            errorCode: $this->deriveErrorCode($exception),
            errorMessage: $this->truncate((string) $exception->getMessage(), 1000),
            metadata: $metadata,
        );

        $this->entityManager->persist($interaction);
        $this->entityManager->flush();
    }

    private function resolveWorkspace(?Assistant $assistant, ?Conversation $conversation): ?Workspace
    {
        if ($conversation !== null) {
            return $conversation->getWorkspace();
        }

        if ($assistant !== null) {
            return $assistant->getWorkspace();
        }

        return null;
    }

    private function extractModel(array $metadata, PromptInterface $prompt): ?string
    {
        $model = $metadata['model'] ?? null;

        if (is_string($model) && trim($model) !== '') {
            return $model;
        }

        if (method_exists($prompt, 'getMetadata')) {
            /** @var array<string, mixed> $promptMetadata */
            $promptMetadata = $prompt->getMetadata();

            $model = $promptMetadata['model'] ?? null;

            if (is_string($model) && trim($model) !== '') {
                return $model;
            }
        }

        return null;
    }

    /**
     * @return array{prompt_tokens: ?int, completion_tokens: ?int, total_tokens: ?int, cost_cents: ?int}
     */
    private function extractUsage(array $resultMetadata, array $overrides): array
    {
        $usage = $resultMetadata['usage'] ?? null;

        if (!is_array($usage)) {
            $usage = [];
        }

        $mergedUsage = array_merge($usage, $overrides['usage'] ?? []);

        $promptTokens = $this->safeInt($mergedUsage['prompt_tokens'] ?? null);
        $completionTokens = $this->safeInt($mergedUsage['completion_tokens'] ?? null);

        $totalTokens = $this->safeInt($mergedUsage['total_tokens'] ?? null);

        if ($totalTokens === null && $promptTokens !== null && $completionTokens !== null) {
            $totalTokens = $promptTokens + $completionTokens;
        }

        $costCents = $this->safeInt($mergedUsage['cost_cents'] ?? $mergedUsage['total_cost_cents'] ?? null);

        return [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cost_cents' => $costCents,
        ];
    }

    private function safeInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $intValue = (int) $value;

            return $intValue >= 0 ? $intValue : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $metadataOverrides
     *
     * @return array<string, mixed>
     */
    private function buildMetadata(
        PromptInterface $prompt,
        ?ResultInterface $result,
        ?\Throwable $exception,
        ?array $metadataOverrides
    ): array {
        $metadata = [
            'prompt' => [
                'type' => $prompt->getType()->value,
                'metadata' => $this->extractPromptMetadata($prompt),
                'details' => $this->extractPromptDetails($prompt),
            ],
        ];

        if ($result !== null) {
            $metadata['result'] = [
                'type' => $result->getType()->value,
                'content' => $this->extractResultContent($result),
                'metadata' => $result->getMetadata(),
            ];
        }

        if ($exception !== null) {
            $metadata['exception'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        if ($metadataOverrides !== null) {
            $metadata['overrides'] = $metadataOverrides;
        }

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPromptMetadata(PromptInterface $prompt): array
    {
        if (!method_exists($prompt, 'getMetadata')) {
            return [];
        }

        /** @var mixed $metadata */
        $metadata = $prompt->getMetadata();

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPromptDetails(PromptInterface $prompt): array
    {
        return match ($prompt->getType()) {
            PromptType::TEXT => $this->describeTextPrompt($prompt),
            PromptType::IMAGE_GENERATION => $this->describeImagePrompt($prompt),
            PromptType::COMPUTER_VISION => $this->describeVisionPrompt($prompt),
            PromptType::AUDIO_TO_TEXT => $this->describeAudioPrompt($prompt),
        };
    }

    private function summarisePrompt(PromptInterface $prompt): ?string
    {
        return match ($prompt->getType()) {
            PromptType::TEXT => $this->summariseTextPrompt($prompt),
            PromptType::IMAGE_GENERATION => $this->summariseImageGenerationPrompt($prompt),
            PromptType::COMPUTER_VISION => $this->summariseComputerVisionPrompt($prompt),
            PromptType::AUDIO_TO_TEXT => $this->summariseAudioPrompt($prompt),
        };
    }

    private function summariseTextPrompt(PromptInterface $prompt): ?string
    {
        if (!$prompt instanceof TextPrompt) {
            return null;
        }

        $messages = $prompt->getMessages();

        if ($messages === []) {
            return null;
        }

        $index = count($messages) - 1;
        $lastMessage = null;

        while ($index >= 0) {
            $candidate = $messages[$index];
            $role = $candidate->getRole();

            if ($role === TextPromptRole::TOOL) {
                $index--;

                continue;
            }

            if ($role === TextPromptRole::SYSTEM) {
                if ($index > 0) {
                    $index--;

                    continue;
                }

                $lastMessage = $candidate;

                break;
            }

            $lastMessage = $candidate;

            break;
        }

        if ($lastMessage === null) {
            $lastMessage = $messages[count($messages) - 1];
        }


        return $this->truncate($lastMessage->getContent(), 120);
    }

    private function extractResultContent(ResultInterface $result): mixed
    {
        if (method_exists($result, 'getContent')) {
            return $result->getContent();
        }

        if (method_exists($result, 'getImages')) {
            return $result->getImages();
        }

        return null;
    }

    private function summariseImageGenerationPrompt(PromptInterface $prompt): ?string
    {
        if (!method_exists($prompt, 'getDescription')) {
            return null;
        }

        /** @var string $description */
        $description = $prompt->getDescription();

        return $this->truncate($description, 120);
    }

    private function summariseComputerVisionPrompt(PromptInterface $prompt): ?string
    {
        if (!method_exists($prompt, 'getQuestion')) {
            return 'Vision analysis';
        }

        $question = $prompt->getQuestion();

        if ($question === null || trim($question) === '') {
            return 'Vision analysis';
        }

        return $this->truncate($question, 120);
    }

    private function summariseAudioPrompt(PromptInterface $prompt): ?string
    {
        if (method_exists($prompt, 'getLanguageCode')) {
            /** @var string|null $language */
            $language = $prompt->getLanguageCode();

            if ($language !== null && $language !== '') {
                return sprintf('Audio transcription (%s)', $language);
            }
        }

        return 'Audio transcription';
    }

    private function deriveErrorCode(\Throwable $exception): string
    {
        $shortName = (new \ReflectionClass($exception))->getShortName();

        return $shortName !== '' ? $shortName : 'Exception';
    }

    private function truncate(string $value, int $length): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $length - 1)) . '…';
    }

    /**
     * @return array<string, mixed>
     */
    private function describeTextPrompt(TextPrompt $prompt): array
    {
        return [
            'messages' => array_map(
                static fn (TextPromptMessage $message): array => [
                    'role' => $message->getRole()->value,
                    'content' => $message->getContent(),
                    'metadata' => $message->getMetadata(),
                ],
                $prompt->getMessages(),
            ),
            'temperature' => $prompt->getTemperature(),
            'maxOutputTokens' => $prompt->getMaxOutputTokens(),
            'tools' => array_map(
                static fn (ToolDefinition $tool): array => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'type' => $tool->getType()->value,
                    'parameters' => $tool->getParameters(),
                    'configuration' => $tool->getConfiguration(),
                ],
                $prompt->getTools(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeImagePrompt(PromptInterface $prompt): array
    {
        if (!method_exists($prompt, 'getDescription')) {
            return [];
        }

        return [
            'description' => $prompt->getDescription(),
            'width' => method_exists($prompt, 'getWidth') ? $prompt->getWidth() : null,
            'height' => method_exists($prompt, 'getHeight') ? $prompt->getHeight() : null,
            'stylePreset' => method_exists($prompt, 'getStylePreset') ? $prompt->getStylePreset() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeVisionPrompt(PromptInterface $prompt): array
    {
        if (!method_exists($prompt, 'getImage')) {
            return [];
        }

        return [
            'question' => method_exists($prompt, 'getQuestion') ? $prompt->getQuestion() : null,
            'image' => [
                'type' => $prompt->getImage()->getType()->value,
                'value' => $prompt->getImage()->getValue(),
                'mimeType' => $prompt->getImage()->getMimeType(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function describeAudioPrompt(PromptInterface $prompt): array
    {
        return [
            'language' => method_exists($prompt, 'getLanguageCode') ? $prompt->getLanguageCode() : null,
            'metadata' => method_exists($prompt, 'getMetadata') ? $prompt->getMetadata() : [],
        ];
    }
}
