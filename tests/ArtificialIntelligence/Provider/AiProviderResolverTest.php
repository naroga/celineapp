<?php

namespace App\Tests\ArtificialIntelligence\Provider;

use App\ArtificialIntelligence\Exception\UnknownProviderException;
use App\ArtificialIntelligence\Exception\UnsupportedPromptException;
use App\ArtificialIntelligence\Provider\AbstractAiProvider;
use App\ArtificialIntelligence\Provider\AiProviderRegistry;
use App\ArtificialIntelligence\Provider\AiProviderResolver;
use App\ArtificialIntelligence\Provider\ProviderResponse;
use App\ArtificialIntelligence\Prompt\ImageGenerationPrompt;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\TextResult;
use App\Entity\Assistant;
use App\Entity\User;
use App\Entity\Workspace;
use PHPUnit\Framework\TestCase;

final class AiProviderResolverTest extends TestCase
{
    public function testResolveDefaultProvider(): void
    {
        $textOnlyProvider = new class('primary') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::TEXT);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return ProviderResponse::fromResult(new TextResult($this->name, 'stub'));
            }
        };

        $resolver = new AiProviderResolver(
            new AiProviderRegistry([$textOnlyProvider]),
            ['default' => 'primary'],
        );

        $provider = $resolver->resolveDefault();

        self::assertSame('primary', $provider->getName());
    }

    public function testResolveForPromptFallsBackToSupportingProvider(): void
    {
        $textProvider = new class('texty') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::TEXT);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return ProviderResponse::fromResult(new TextResult($this->name, 'text'));
            }
        };

        $imageProvider = new class('imago') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::IMAGE_GENERATION);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return ProviderResponse::fromResult(new TextResult($this->name, 'image-result'));
            }
        };

        $resolver = new AiProviderResolver(
            new AiProviderRegistry([$textProvider, $imageProvider]),
            ['default' => 'texty'],
        );

        $provider = $resolver->resolveForPrompt(new ImageGenerationPrompt('Create a logo'));

        self::assertSame('imago', $provider->getName());
    }

    public function testResolveForAssistantHonoursOverride(): void
    {
        $defaultProvider = new class('default') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::TEXT);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return ProviderResponse::fromResult(new TextResult($this->name, 'default'));
            }
        };

        $overrideProvider = new class('override') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::TEXT);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return ProviderResponse::fromResult(new TextResult($this->name, 'override'));
            }
        };

        $assistant = $this->createAssistant();

        $resolver = new AiProviderResolver(
            new AiProviderRegistry([$defaultProvider, $overrideProvider]),
            ['default' => 'default', 'assistant_overrides' => [$assistant->getId() => 'override']],
        );

        $provider = $resolver->resolveForAssistant($assistant);

        self::assertSame('override', $provider->getName());
    }

    public function testResolveForPromptPrefersAssistantDefaultProvider(): void
    {
        $textProvider = new class('texty') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::TEXT);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return new TextResult($this->name, 'text');
            }
        };

        $imageProvider = new class('imago') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::IMAGE_GENERATION);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return new TextResult($this->name, 'image');
            }
        };

        $assistant = $this->createAssistant();
        $assistant->setDefaultProvider('imago');

        $resolver = new AiProviderResolver(
            new AiProviderRegistry([$textProvider, $imageProvider]),
            ['default' => 'texty'],
        );

        $provider = $resolver->resolveForPrompt(new ImageGenerationPrompt('logo'), null, $assistant);

        self::assertSame('imago', $provider->getName());
    }

    public function testResolveUnknownProviderThrows(): void
    {
        $this->expectException(UnknownProviderException::class);

        $resolver = new AiProviderResolver(new AiProviderRegistry(), ['default' => 'missing']);
        $resolver->resolve('missing');
    }

    public function testResolveForPromptWithoutSupportingProviderThrows(): void
    {
        $textProvider = new class('texty') extends AbstractAiProvider {
            public function __construct(private readonly string $name)
            {
                parent::__construct($name, PromptType::TEXT);
            }

            public function process(PromptInterface $prompt): ProviderResponse
            {
                return new TextResult($this->name, 'text');
            }
        };

        $resolver = new AiProviderResolver(
            new AiProviderRegistry([$textProvider]),
            ['default' => 'texty'],
        );

        $this->expectException(UnsupportedPromptException::class);

        $resolver->resolveForPrompt(new ImageGenerationPrompt('make art'));
    }

    public function testTextPromptRequiresAtLeastOneMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextPrompt([]);
    }

    public function testTextPromptMessageCannotBeEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TextPromptMessage(TextPromptRole::USER, '');
    }

    public function testTextPromptAcceptsValidMessages(): void
    {
        $message = new TextPromptMessage(TextPromptRole::USER, 'Hello');
        $prompt = new TextPrompt([$message]);

        self::assertSame([$message], $prompt->getMessages());
        self::assertNull($prompt->getTemperature());
    }

    private function createAssistant(): Assistant
    {
        $user = new User('owner@example.com', 'Owner', 'Example');
        $workspace = new Workspace($user, 'Example Workspace');

        return new Assistant(
            $workspace,
            'Test Assistant',
            'female',
            'https://example.com/avatar.png',
            'assistant@example.com',
            '+15555555555',
            'Follow the workspace playbook.',
        );
    }
}
