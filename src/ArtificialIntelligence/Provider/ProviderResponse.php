<?php

namespace App\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Result\ResultInterface;
use App\ArtificialIntelligence\Tool\ToolCall;

final class ProviderResponse
{
    /**
     * @param list<ToolCall> $toolCalls
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        private readonly ?ResultInterface $result,
        private readonly array $toolCalls,
        private readonly array $metadata,
    ) {
        if ($result !== null && $toolCalls !== []) {
            throw new \InvalidArgumentException('Provider responses cannot contain both a result and tool calls simultaneously.');
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function fromResult(ResultInterface $result, array $metadata = []): self
    {
        return new self($result, [], $metadata);
    }

    /**
     * @param list<ToolCall> $toolCalls
     * @param array<string, mixed> $metadata
     */
    public static function fromToolCalls(array $toolCalls, array $metadata = []): self
    {
        foreach ($toolCalls as $call) {
            if (!$call instanceof ToolCall) {
                throw new \InvalidArgumentException('Provider tool responses must be composed of ToolCall instances.');
            }
        }

        return new self(null, array_values($toolCalls), $metadata);
    }

    public function hasResult(): bool
    {
        return $this->result !== null;
    }

    public function isToolContinuation(): bool
    {
        return $this->toolCalls !== [];
    }

    public function getResult(): ?ResultInterface
    {
        return $this->result;
    }

    /**
     * @return list<ToolCall>
     */
    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
