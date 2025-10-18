<?php

namespace App\ArtificialIntelligence\Tool;

/**
 * Represents a tool invocation requested by an AI provider.
 */
final class ToolCall
{
    private readonly string $name;

    /**
     * @var array<string, mixed>
     */
    private readonly array $arguments;

    private readonly ?string $callId;

    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        string $name,
        array $arguments = [],
        ?string $callId = null,
    ) {
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw new \InvalidArgumentException('Tool calls must include a tool name.');
        }

        $this->assertArgumentsSerializable($arguments);

        $this->name = $trimmedName;
        $this->arguments = $arguments;
        $this->callId = $callId !== null && $callId !== '' ? $callId : null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getCallId(): ?string
    {
        return $this->callId;
    }

    private function assertArgumentsSerializable(array $arguments): void
    {
        $encoded = json_encode($arguments);

        if ($encoded === false) {
            throw new \InvalidArgumentException('Tool call arguments must be JSON serialisable.');
        }
    }
}
