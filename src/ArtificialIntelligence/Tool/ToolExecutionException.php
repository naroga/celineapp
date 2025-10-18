<?php

namespace App\ArtificialIntelligence\Tool;

use Throwable;

final class ToolExecutionException extends \RuntimeException
{
    public static function unknownTool(string $name): self
    {
        return new self(sprintf('Requested tool "%s" is not registered.', $name));
    }

    public static function executionFailed(string $name, Throwable $previous): self
    {
        return new self(sprintf('Tool "%s" execution failed: %s', $name, $previous->getMessage()), 0, $previous);
    }
}
