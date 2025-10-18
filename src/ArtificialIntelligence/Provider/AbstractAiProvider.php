<?php

namespace App\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;

abstract class AbstractAiProvider implements AiProviderInterface
{
    /**
     * @var list<PromptType>
     */
    private readonly array $supportedPromptTypes;

    public function __construct(private readonly string $name, PromptType ...$supportedPromptTypes)
    {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('AI providers must declare a name.');
        }

        $this->supportedPromptTypes = $supportedPromptTypes === []
            ? [PromptType::TEXT]
            : $supportedPromptTypes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function supports(PromptInterface $prompt): bool
    {
        return in_array($prompt->getType(), $this->supportedPromptTypes, true);
    }
}
