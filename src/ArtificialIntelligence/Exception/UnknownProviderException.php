<?php

namespace App\ArtificialIntelligence\Exception;

final class UnknownProviderException extends ArtificialIntelligenceException
{
    public static function forName(string $provider): self
    {
        return new self(sprintf('AI provider "%s" is not registered.', $provider));
    }
}
