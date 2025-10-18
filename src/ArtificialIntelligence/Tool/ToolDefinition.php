<?php

namespace App\ArtificialIntelligence\Tool;

/**
 * Represents a tool that can be exposed to an AI provider.
 *
 * Tools can either be backed by built-in functions or external MCP servers. The
 * definition encapsulates the metadata required by the LLM as well as any
 * configuration the runtime needs to execute the tool.
 */
final class ToolDefinition
{
    private readonly string $name;

    private readonly string $description;

    /**
     * @var array<string, mixed>
     */
    private readonly array $parameters;

    private readonly ToolDefinitionType $type;

    /**
     * @var array<string, mixed>
     */
    private readonly array $configuration;

    /**
     * @param array<string, mixed> $parameters JSON schema describing the tool arguments.
     * @param array<string, mixed> $configuration Execution-specific configuration (e.g. MCP server id).
     */
    public function __construct(
        string $name,
        string $description,
        array $parameters = [],
        ToolDefinitionType $type = ToolDefinitionType::FUNCTION,
        array $configuration = [],
    ) {
        $trimmedName = trim($name);

        if ($trimmedName === '') {
            throw new \InvalidArgumentException('Tool definitions must have a name.');
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $trimmedName)) {
            throw new \InvalidArgumentException('Tool names may only contain letters, numbers, dots, dashes, and underscores.');
        }

        $trimmedDescription = trim($description);

        if ($trimmedDescription === '') {
            throw new \InvalidArgumentException('Tool definitions must include a description.');
        }

        $this->assertArrayIsJsonSerializable($parameters, 'parameters');
        $this->assertArrayIsJsonSerializable($configuration, 'configuration');

        $this->name = $trimmedName;
        $this->description = $trimmedDescription;
        $this->parameters = $parameters;
        $this->type = $type;
        $this->configuration = $configuration;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getType(): ToolDefinitionType
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    private function assertArrayIsJsonSerializable(array $value, string $context): void
    {
        $encoded = json_encode($value);

        if ($encoded === false) {
            throw new \InvalidArgumentException(sprintf('Tool %s must be JSON serialisable.', $context));
        }
    }
}
