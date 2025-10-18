<?php

namespace App\ArtificialIntelligence\Prompt;

interface PromptInterface
{
    public function getType(): PromptType;
}
