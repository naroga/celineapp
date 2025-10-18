<?php

namespace App\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Result\ResultInterface;

interface AiProviderInterface
{
    public function getName(): string;

    public function supports(PromptInterface $prompt): bool;

    public function process(PromptInterface $prompt): ResultInterface;
}
