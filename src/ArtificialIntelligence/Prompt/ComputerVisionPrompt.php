<?php

namespace App\ArtificialIntelligence\Prompt;

/**
 * Represents a visual question answering request, pairing an image with optional instructions.
 *
 * @immutable
 */
final class ComputerVisionPrompt implements PromptInterface
{
    private readonly MediaInput $image;

    private readonly ?string $question;

    private readonly array $metadata;

    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(MediaInput $image, ?string $question = null, array $metadata = [])
    {
        $this->image = $image;
        $this->question = $question;
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::COMPUTER_VISION;
    }

    public function getImage(): MediaInput
    {
        return $this->image;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
