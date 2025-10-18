<?php

namespace App\ArtificialIntelligence\Tool;

use App\Entity\Assistant;
use App\Entity\Conversation;
use App\Entity\Workspace;

/**
 * Provides contextual information about the environment where a tool executes.
 */
final class ToolRuntimeContext
{
    private readonly ?Workspace $workspace;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly ?Assistant $assistant = null,
        private readonly ?Conversation $conversation = null,
        ?Workspace $workspace = null,
        private readonly array $metadata = [],
    ) {
        $resolvedWorkspace = $workspace ?? $conversation?->getWorkspace() ?? $assistant?->getWorkspace();

        $this->workspace = $resolvedWorkspace;

        $this->assertMetadataSerializable($metadata);
    }

    public function getAssistant(): ?Assistant
    {
        return $this->assistant;
    }

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function getWorkspace(): ?Workspace
    {
        return $this->workspace;
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
            throw new \InvalidArgumentException('Tool runtime metadata must be JSON serialisable.');
        }
    }
}
