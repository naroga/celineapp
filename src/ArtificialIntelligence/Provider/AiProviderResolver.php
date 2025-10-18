<?php

namespace App\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Exception\UnknownProviderException;
use App\ArtificialIntelligence\Exception\UnsupportedPromptException;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\Entity\Assistant;
use App\Entity\Conversation;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AiProviderResolver
{
    private readonly string $defaultProvider;

    /**
     * @var array<string, string>
     */
    private readonly array $assistantOverrides;

    /**
     * @var array<string, array<string, mixed>>
     */
    private readonly array $providerConfigurations;

    /**
     * @param array{
     *     default?: string,
     *     assistant_overrides?: array<string, string>,
     *     providers?: array<string, array<string, mixed>>
     * } $configuration
     */
    public function __construct(
        private readonly AiProviderRegistry $registry,
        #[Autowire('%app.ai.providers%')]
        array $configuration = [],
    ) {
        $this->defaultProvider = $configuration['default'] ?? '';
        $this->assistantOverrides = $configuration['assistant_overrides'] ?? [];
        $this->providerConfigurations = $configuration['providers'] ?? [];
    }

    public function getDefaultProvider(): string
    {
        if ($this->defaultProvider === '') {
            throw new \RuntimeException('No default AI provider has been configured.');
        }

        return $this->defaultProvider;
    }

    public function resolve(string $providerName): AiProviderInterface
    {
        if (!$this->registry->has($providerName)) {
            throw UnknownProviderException::forName($providerName);
        }

        return $this->registry->get($providerName);
    }

    public function resolveDefault(): AiProviderInterface
    {
        return $this->resolve($this->getDefaultProvider());
    }

    public function resolveForAssistant(Assistant $assistant): AiProviderInterface
    {
        $assistantId = $assistant->getId();

        if (isset($this->assistantOverrides[$assistantId])) {
            return $this->resolve($this->assistantOverrides[$assistantId]);
        }

        $assistantDefaultProvider = $assistant->getDefaultProvider();

        if ($assistantDefaultProvider !== null) {
            return $this->resolve($assistantDefaultProvider);
        }

        return $this->resolveDefault();
    }

    public function resolveForConversation(PromptInterface $prompt, Conversation $conversation): AiProviderInterface
    {
        $providerName = $conversation->getProviderName();

        if ($providerName !== null) {
            return $this->resolveForPrompt($prompt, $providerName, $conversation->getAssistant());
        }

        return $this->resolveForPrompt($prompt, null, $conversation->getAssistant());
    }

    public function resolveForPrompt(
        PromptInterface $prompt,
        ?string $providerName = null,
        ?Assistant $assistant = null,
    ): AiProviderInterface {
        $explicitProvider = null;

        if ($providerName !== null) {
            try {
                $explicitProvider = $this->resolve($providerName);

                if ($explicitProvider->supports($prompt)) {
                    return $explicitProvider;
                }
            } catch (UnknownProviderException $exception) {
                if ($assistant === null) {
                    throw $exception;
                }
            }
        }

        if ($assistant !== null) {
            try {
                $assistantProvider = $this->resolveForAssistant($assistant);

                if ($assistantProvider->supports($prompt)) {
                    return $assistantProvider;
                }
            } catch (UnknownProviderException $exception) {
                // Ignore invalid assistant overrides and fall through to other providers.
            }
        }

        $provider = $this->resolveDefault();

        if ($provider->supports($prompt)) {
            return $provider;
        }

        $fallbacks = $this->registry->findSupportingPrompt($prompt);

        if ($fallbacks === []) {
            throw UnsupportedPromptException::noneAvailable($prompt);
        }

        return $fallbacks[0];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProviderConfiguration(string $providerName): array
    {
        return $this->providerConfigurations[$providerName] ?? [];
    }
}
