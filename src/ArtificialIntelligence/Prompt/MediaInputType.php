<?php

namespace App\ArtificialIntelligence\Prompt;

enum MediaInputType: string
{
    case FILE_PATH = 'file_path';
    case URL = 'url';
    case BASE64 = 'base64';
}
