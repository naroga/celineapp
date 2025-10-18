<?php

namespace App\ArtificialIntelligence\Prompt;

/**
 * Represents a transcription request for an audio clip.
 *
 * @immutable
 */
final class AudioToTextPrompt implements PromptInterface
{
    private readonly MediaInput $audio;

    private readonly ?string $languageCode;

    private readonly array $metadata;

    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(MediaInput $audio, ?string $languageCode = null, array $metadata = [])
    {
        $this->audio = $audio;
        $this->languageCode = $languageCode;
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::AUDIO_TO_TEXT;
    }

    public function getAudio(): MediaInput
    {
        return $this->audio;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
