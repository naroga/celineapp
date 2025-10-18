<?php

namespace App\ArtificialIntelligence\Result;

use App\ArtificialIntelligence\Prompt\PromptType;

interface ResultInterface
{
    public function getType(): PromptType;

    public function getProviderName(): string;

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
