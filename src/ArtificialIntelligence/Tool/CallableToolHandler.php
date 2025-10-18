<?php

namespace App\ArtificialIntelligence\Tool;

use Closure;
use JsonException;

final class CallableToolHandler implements ToolHandlerInterface
{
    private readonly Closure $handler;

    /**
     * @param callable(array<string, mixed>, ToolRuntimeContext): (ToolExecutionResult|array<string, mixed>|string) $handler
     */
    public function __construct(
        private readonly ToolDefinition $definition,
        callable $handler,
    ) {
        if ($definition->getType() !== ToolDefinitionType::FUNCTION) {
            throw new \InvalidArgumentException('Callable tool handlers require a ToolDefinition of type FUNCTION.');
        }

        $this->handler = Closure::fromCallable($handler);
    }

    public function getDefinition(): ToolDefinition
    {
        return $this->definition;
    }

    public function execute(ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult
    {
        $result = ($this->handler)($call->getArguments(), $context);

        return $this->normaliseResult($result);
    }

    private function normaliseResult(mixed $result): ToolExecutionResult
    {
        if ($result instanceof ToolExecutionResult) {
            return $result;
        }

        if (is_string($result)) {
            return new ToolExecutionResult($result);
        }

        if (is_array($result)) {
            try {
                $encoded = json_encode($result, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new \RuntimeException('Callable tool handler returned non-serialisable array output.', 0, $exception);
            }

            return new ToolExecutionResult($encoded, ['structured' => $result]);
        }

        throw new \RuntimeException('Callable tool handlers must return a ToolExecutionResult, string, or array.');
    }
}
