<?php

namespace App\ArtificialIntelligence\Prompt;

/**
 * Represents a chat-based text prompt that can include prior conversation context.
 *
 * The prompt is intentionally provider-agnostic; individual providers are
 * responsible for translating these messages and settings into their native format.
 *
 * @immutable
 */
final class TextPrompt implements PromptInterface
{
    /**
     * @var list<TextPromptMessage>
     */
    private readonly array $messages;

    private readonly ?float $temperature;

    private readonly ?int $maxOutputTokens;

    private readonly array $metadata;

    /**
     * @param list<TextPromptMessage> $messages
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(array $messages, ?float $temperature = null, ?int $maxOutputTokens = null, array $metadata = [])
    {
        if ($messages === []) {
            throw new \InvalidArgumentException('A text prompt requires at least one message.');
        }

        foreach ($messages as $message) {
            if (!$message instanceof TextPromptMessage) {
                throw new \InvalidArgumentException('Text prompts only accept TextPromptMessage instances.');
            }
        }

        if ($temperature !== null && ($temperature < 0.0 || $temperature > 2.0)) {
            throw new \InvalidArgumentException('Temperature must be between 0.0 and 2.0.');
        }

        if ($maxOutputTokens !== null && $maxOutputTokens <= 0) {
            throw new \InvalidArgumentException('Max output tokens must be a positive integer.');
        }

        $this->messages = array_values($messages);
        $this->temperature = $temperature;
        $this->maxOutputTokens = $maxOutputTokens;
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::TEXT;
    }

    /**
     * @return list<TextPromptMessage>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function getMaxOutputTokens(): ?int
    {
        return $this->maxOutputTokens;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
