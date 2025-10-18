<?php

namespace App\ArtificialIntelligence\Exception;

use App\ArtificialIntelligence\Prompt\PromptInterface;

final class UnsupportedPromptException extends ArtificialIntelligenceException
{
    public static function forProvider(string $provider, PromptInterface $prompt): self
    {
        return new self(sprintf('Provider "%s" does not support prompt type "%s".', $provider, $prompt->getType()->value));
    }

    public static function noneAvailable(PromptInterface $prompt): self
    {
        return new self(sprintf('No AI providers are registered for prompt type "%s".', $prompt->getType()->value));
    }
}
