<?php

namespace App\ArtificialIntelligence\Tool\Mcp;

use App\ArtificialIntelligence\Tool\ToolCall;
use App\ArtificialIntelligence\Tool\ToolDefinition;
use App\ArtificialIntelligence\Tool\ToolDefinitionType;
use App\ArtificialIntelligence\Tool\ToolExecutionResult;
use App\ArtificialIntelligence\Tool\ToolHandlerInterface;
use App\ArtificialIntelligence\Tool\ToolRuntimeContext;

final class McpToolHandler implements ToolHandlerInterface
{
    public function __construct(
        private readonly ToolDefinition $definition,
        private readonly McpTransportInterface $transport,
    ) {
        if ($definition->getType() !== ToolDefinitionType::MCP) {
            throw new \InvalidArgumentException('MCP tool handlers require a ToolDefinition of type MCP.');
        }
    }

    public function getDefinition(): ToolDefinition
    {
        return $this->definition;
    }

    public function execute(ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult
    {
        $server = $this->definition->getConfiguration()['server'] ?? null;

        if (!is_string($server) || trim($server) === '') {
            throw new \RuntimeException(sprintf('MCP tool "%s" is missing a "server" configuration value.', $this->definition->getName()));
        }

        return $this->transport->execute($server, $call, $context);
    }
}
