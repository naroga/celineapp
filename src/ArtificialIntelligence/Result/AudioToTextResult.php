<?php

namespace App\ArtificialIntelligence\Result;

use App\ArtificialIntelligence\Prompt\PromptType;

final class AudioToTextResult implements ResultInterface
{
    private readonly string $providerName;

    private readonly string $transcript;

    private readonly array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(string $providerName, string $transcript, array $metadata = [])
    {
        if (trim($providerName) === '') {
            throw new \InvalidArgumentException('Provider name is required for AI results.');
        }

        $this->providerName = $providerName;
        $this->transcript = $transcript;
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::AUDIO_TO_TEXT;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getTranscript(): string
    {
        return $this->transcript;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
