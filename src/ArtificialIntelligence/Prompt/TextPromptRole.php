<?php

namespace App\ArtificialIntelligence\Prompt;

enum TextPromptRole: string
{
    case SYSTEM = 'system';
    case USER = 'user';
    case ASSISTANT = 'assistant';
}
