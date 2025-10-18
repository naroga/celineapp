<?php

namespace App\ArtificialIntelligence\Provider\Gemini;

use App\ArtificialIntelligence\Provider\AbstractAiProvider;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Result\ResultInterface;

final class GeminiProvider extends AbstractAiProvider
{
    public function __construct()
    {
        parent::__construct(
            'gemini',
            PromptType::TEXT,
            PromptType::IMAGE_GENERATION,
            PromptType::COMPUTER_VISION,
        );
    }

    public function process(PromptInterface $prompt): ResultInterface
    {
        throw new \LogicException('Gemini integration is not implemented yet.');
    }
}
