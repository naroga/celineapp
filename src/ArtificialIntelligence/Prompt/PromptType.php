<?php

namespace App\ArtificialIntelligence\Prompt;

enum PromptType: string
{
    case TEXT = 'text';
    case IMAGE_GENERATION = 'image_generation';
    case COMPUTER_VISION = 'computer_vision';
    case AUDIO_TO_TEXT = 'audio_to_text';
}
