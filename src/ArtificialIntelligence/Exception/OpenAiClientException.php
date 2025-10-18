<?php

namespace App\ArtificialIntelligence\Exception;

final class OpenAiClientException extends ArtificialIntelligenceException
{
    public static function missingApiKey(): self
    {
        return new self('OpenAI API key is not configured.');
    }

    public static function network(\Throwable $previous): self
    {
        return new self('OpenAI request failed due to a network error.', previous: $previous);
    }

    public static function fromResponse(int $statusCode, string $message): self
    {
        return new self(sprintf('OpenAI request failed (%d): %s', $statusCode, $message));
    }

    public static function invalidResponse(string $reason, \Throwable $previous = null): self
    {
        return new self(sprintf('OpenAI returned an invalid response: %s', $reason), previous: $previous);
    }
}
