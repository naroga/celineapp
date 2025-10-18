<?php

namespace App\ArtificialIntelligence\Result;

enum GeneratedImageType: string
{
    case URL = 'url';
    case BASE64 = 'base64';
}

final class GeneratedImage
{
    private readonly GeneratedImageType $type;

    private readonly string $value;

    private readonly ?string $mimeType;

    private const DEFAULT_BASE64_MIME_TYPE = 'image/png';

    public function __construct(GeneratedImageType $type, string $value, ?string $mimeType = null)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Generated images require a value.');
        }

        $this->type = $type;
        $this->value = $value;
        $this->mimeType = $mimeType;
    }

    public function getType(): GeneratedImageType
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getMimeType(): ?string
    {
        if ($this->mimeType !== null) {
            return $this->mimeType;
        }

        if ($this->type === GeneratedImageType::BASE64) {
            return self::DEFAULT_BASE64_MIME_TYPE;
        }

        return null;
    }
}
