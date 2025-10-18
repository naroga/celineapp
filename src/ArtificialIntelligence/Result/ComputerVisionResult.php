<?php

namespace App\ArtificialIntelligence\Result;

use App\ArtificialIntelligence\Prompt\PromptType;

final class ComputerVisionResult implements ResultInterface
{
    private readonly string $providerName;

    private readonly string $content;

    private readonly array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(string $providerName, string $content, array $metadata = [])
    {
        if (trim($providerName) === '') {
            throw new \InvalidArgumentException('Provider name is required for AI results.');
        }

        $this->providerName = $providerName;
        $this->content = $content;
        $this->metadata = $metadata;
    }

    public function getType(): PromptType
    {
        return PromptType::COMPUTER_VISION;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
