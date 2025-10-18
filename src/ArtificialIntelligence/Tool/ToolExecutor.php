<?php

namespace App\ArtificialIntelligence\Tool;

use Throwable;

final class ToolExecutor
{
    public function __construct(private readonly ToolRegistry $registry)
    {
    }

    public function execute(ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult
    {
        $handler = $this->registry->get($call->getName());

        try {
            return $handler->execute($call, $context);
        } catch (ToolExecutionException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ToolExecutionException::executionFailed($call->getName(), $exception);
        }
    }

    /**
     * @param iterable<ToolCall> $calls
     * @return list<ToolExecutionResult>
     */
    public function executeMany(iterable $calls, ToolRuntimeContext $context): array
    {
        $results = [];

        foreach ($calls as $call) {
            $results[] = $this->execute($call, $context);
        }

        return $results;
    }
}
