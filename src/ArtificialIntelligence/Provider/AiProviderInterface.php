<?php

namespace App\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Prompt\PromptInterface;

interface AiProviderInterface
{
    public function getName(): string;

    public function supports(PromptInterface $prompt): bool;

    public function process(PromptInterface $prompt): ProviderResponse;
}
