<?php

namespace App\ArtificialIntelligence\Conversation;

enum ConversationRole: string
{
    case SYSTEM = 'system';
    case USER = 'user';
    case ASSISTANT = 'assistant';
}
