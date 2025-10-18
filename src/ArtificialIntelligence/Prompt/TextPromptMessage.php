<?php

namespace App\ArtificialIntelligence\Prompt;

final class TextPromptMessage
{
    private readonly TextPromptRole $role;

    private readonly string $content;

    public function __construct(TextPromptRole $role, string $content)
    {
        if (trim($content) === '') {
            throw new \InvalidArgumentException('Text prompt messages must have content.');
        }

        $this->role = $role;
        $this->content = $content;
    }

    public function getRole(): TextPromptRole
    {
        return $this->role;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
