<?php

namespace App\ArtificialIntelligence\Tool;

/**
 * Represents the outcome of executing a tool call.
 */
final class ToolExecutionResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $content,
        private readonly array $metadata = [],
    ) {
        if (trim($content) === '') {
            throw new \InvalidArgumentException('Tool execution results must include content.');
        }

        $this->assertMetadataSerializable($metadata);
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

    private function assertMetadataSerializable(array $metadata): void
    {
        $encoded = json_encode($metadata);

        if ($encoded === false) {
            throw new \InvalidArgumentException('Tool execution metadata must be JSON serialisable.');
        }
    }
}

