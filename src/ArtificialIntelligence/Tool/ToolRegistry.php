<?php

namespace App\ArtificialIntelligence\Tool;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class ToolRegistry
{
    /**
     * @var array<string, ToolHandlerInterface>
     */
    private array $handlers = [];

    /**
     * @param iterable<ToolHandlerInterface> $handlers
     */
    public function __construct(#[TaggedIterator('app.ai_tool')] iterable $handlers = [])
    {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    public function register(ToolHandlerInterface $handler): void
    {
        $definition = $handler->getDefinition();
        $name = $definition->getName();

        if (isset($this->handlers[$name])) {
            throw new \InvalidArgumentException(sprintf('A tool handler with the name "%s" is already registered.', $name));
        }

        $this->handlers[$name] = $handler;
    }

    public function has(string $name): bool
    {
        return isset($this->handlers[$name]);
    }

    public function get(string $name): ToolHandlerInterface
    {
        if (!$this->has($name)) {
            throw ToolExecutionException::unknownTool($name);
        }

        return $this->handlers[$name];
    }

    public function getDefinition(string $name): ToolDefinition
    {
        return $this->get($name)->getDefinition();
    }

    /**
     * @return array<string, ToolHandlerInterface>
     */
    public function all(): array
    {
        return $this->handlers;
    }

    /**
     * @return array<string, ToolDefinition>
     */
    public function getDefinitions(): array
    {
        $definitions = [];

        foreach ($this->handlers as $name => $handler) {
            $definitions[$name] = $handler->getDefinition();
        }

        return $definitions;
    }
}
