<?php

namespace App\ArtificialIntelligence\Prompt;

/**
 * Represents a request to create images based on a textual description.
 *
 * Width and height are expressed in pixels. Providers that only support predefined
 * aspect ratios can interpret the closest compatible configuration.
 *
 * @immutable
 */
final class ImageGenerationPrompt implements PromptInterface
{
    private readonly string $description;

    private readonly ?int $width;

    private readonly ?int $height;

    private readonly ?string $stylePreset;

    private readonly array $metadata;

    /**
     * @param array<string, scalar|null> $metadata
     */
    public function __construct(string $description, ?int $width = null, ?int $height = null, ?string $stylePreset = null, array $metadata = [])
    {
        if (trim($description) === '') {
            throw new \InvalidArgumentException('Image generation prompts require a description.');
        }

        if ($width !== null && $width <= 0) {
            throw new \InvalidArgumentException('Width must be a positive integer.');
        }

        if ($height !== null && $height <= 0) {
            throw new \InvalidArgumentException('Height must be a positive integer.');
        }

        $this->description = $description;
        $this->width = $width;
        $this->height = $height;
        $this->stylePreset = $stylePreset;
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::IMAGE_GENERATION;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getStylePreset(): ?string
    {
        return $this->stylePreset;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
