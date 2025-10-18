<?php

namespace App\Tests\ArtificialIntelligence\Tool;

use App\ArtificialIntelligence\Tool\CallableToolHandler;
use App\ArtificialIntelligence\Tool\ToolCall;
use App\ArtificialIntelligence\Tool\ToolDefinition;
use App\ArtificialIntelligence\Tool\ToolDefinitionType;
use App\ArtificialIntelligence\Tool\ToolExecutionException;
use App\ArtificialIntelligence\Tool\ToolExecutionResult;
use App\ArtificialIntelligence\Tool\ToolExecutor;
use App\ArtificialIntelligence\Tool\ToolHandlerInterface;
use App\ArtificialIntelligence\Tool\ToolRegistry;
use App\ArtificialIntelligence\Tool\ToolRuntimeContext;
use PHPUnit\Framework\TestCase;

final class ToolExecutorTest extends TestCase
{
    public function testExecuteDelegatesToRegisteredHandler(): void
    {
        $definition = new ToolDefinition('echo', 'Echo input.', [
            'type' => 'object',
            'properties' => ['value' => ['type' => 'string']],
        ], ToolDefinitionType::FUNCTION);

        $handler = new CallableToolHandler($definition, static function (array $arguments): string {
            return (string) ($arguments['value'] ?? 'missing');
        });

        $executor = new ToolExecutor(new ToolRegistry([$handler]));
        $result = $executor->execute(new ToolCall('echo', ['value' => 'hello'], 'call-1'), new ToolRuntimeContext());

        self::assertSame('hello', $result->getContent());
    }

    public function testExecuteThrowsWhenToolUnknown(): void
    {
        $executor = new ToolExecutor(new ToolRegistry());

        $this->expectException(ToolExecutionException::class);
        $this->expectExceptionMessage('Requested tool "missing" is not registered.');

        $executor->execute(new ToolCall('missing'), new ToolRuntimeContext());
    }

    public function testExecuteWrapsHandlerExceptions(): void
    {
        $definition = new ToolDefinition('fail', 'Always fails.', [], ToolDefinitionType::FUNCTION);

        $handler = new class($definition) implements ToolHandlerInterface {
            public function __construct(private readonly ToolDefinition $definition)
            {
            }

            public function getDefinition(): ToolDefinition
            {
                return $this->definition;
            }

            public function execute(ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult
            {
                throw new \RuntimeException('Boom');
            }
        };

        $executor = new ToolExecutor(new ToolRegistry([$handler]));

        $this->expectException(ToolExecutionException::class);
        $this->expectExceptionMessage('Tool "fail" execution failed');

        $executor->execute(new ToolCall('fail'), new ToolRuntimeContext());
    }
}
