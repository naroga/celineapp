<?php

namespace App\ArtificialIntelligence\Tool;

interface ToolHandlerInterface
{
    public function getDefinition(): ToolDefinition;

    public function execute(ToolCall $call, ToolRuntimeContext $context): ToolExecutionResult;
}
