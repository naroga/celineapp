<?php

namespace App\ArtificialIntelligence\Provider\Anthropic;

use App\ArtificialIntelligence\Provider\AbstractAiProvider;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Provider\ProviderResponse;

final class AnthropicProvider extends AbstractAiProvider
{
    public function __construct()
    {
        parent::__construct(
            'anthropic',
            PromptType::TEXT,
            PromptType::COMPUTER_VISION,
        );
    }

    public function process(PromptInterface $prompt): ProviderResponse
    {
        throw new \LogicException('Anthropic integration is not implemented yet.');
    }
}
