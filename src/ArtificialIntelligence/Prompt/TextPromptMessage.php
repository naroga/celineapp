<?php

namespace App\ArtificialIntelligence\Prompt;

final class TextPromptMessage
{
    private readonly TextPromptRole $role;

    private readonly string $content;

    /**
     * @var array<string, mixed>
     */
    private readonly array $metadata;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(TextPromptRole $role, string $content, array $metadata = [])
    {
        if (trim($content) === '') {
            throw new \InvalidArgumentException('Text prompt messages must have content.');
        }

        foreach ($metadata as $key => $value) {
            if (!is_string($key) || $key === '') {
                throw new \InvalidArgumentException('Text prompt message metadata keys must be non-empty strings.');
            }

            if (!is_scalar($value) && $value !== null && !is_array($value)) {
                throw new \InvalidArgumentException('Text prompt message metadata values must be scalars, arrays, or null.');
            }
        }

        $this->role = $role;
        $this->content = $content;
        $this->metadata = $metadata;
    }

    public function getRole(): TextPromptRole
    {
        return $this->role;
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
