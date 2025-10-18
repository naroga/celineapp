<?php

namespace App\ArtificialIntelligence\Tool\Mcp;

use App\ArtificialIntelligence\Tool\ToolCall;
use App\ArtificialIntelligence\Tool\ToolExecutionResult;
use App\ArtificialIntelligence\Tool\ToolRuntimeContext;

interface McpTransportInterface
{
    public function execute(string $serverName, ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult;
}
