<?php

namespace App\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Prompt\PromptInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class AiProviderRegistry
{
    /**
     * @var array<string, AiProviderInterface>
     */
    private array $providers = [];

    /**
     * @param iterable<AiProviderInterface> $providers
     */
    public function __construct(#[TaggedIterator('app.ai_provider')] iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->add($provider);
        }
    }

    public function add(AiProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * @return array<string, AiProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    public function get(string $name): AiProviderInterface
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('AI provider "%s" is not registered.', $name));
        }

        return $this->providers[$name];
    }

    /**
     * @return list<AiProviderInterface>
     */
    public function findSupportingPrompt(PromptInterface $prompt): array
    {
        $matches = [];

        foreach ($this->providers as $provider) {
            if ($provider->supports($prompt)) {
                $matches[] = $provider;
            }
        }

        return $matches;
    }
}
