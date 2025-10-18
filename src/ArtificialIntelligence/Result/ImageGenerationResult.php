<?php

namespace App\ArtificialIntelligence\Result;

use App\ArtificialIntelligence\Prompt\PromptType;

final class ImageGenerationResult implements ResultInterface
{
    private readonly string $providerName;

    /**
     * @var list<GeneratedImage>
     */
    private readonly array $images;

    private readonly array $metadata;

    /**
     * @param list<GeneratedImage> $images
     * @param array<string, mixed> $metadata
     */
    public function __construct(string $providerName, array $images, array $metadata = [])
    {
        if (trim($providerName) === '') {
            throw new \InvalidArgumentException('Provider name is required for AI results.');
        }

        foreach ($images as $image) {
            if (!$image instanceof GeneratedImage) {
                throw new \InvalidArgumentException('Images must be GeneratedImage instances.');
            }
        }

        if ($images === []) {
            throw new \InvalidArgumentException('Image generation results require at least one image.');
        }

        $this->providerName = $providerName;
        $this->images = array_values($images);
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::IMAGE_GENERATION;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    /**
     * @return list<GeneratedImage>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
