<?php

namespace App\ArtificialIntelligence\Prompt;

final class MediaInput
{
    private readonly MediaInputType $type;

    private readonly string $source;

    private readonly ?string $mimeType;

    public function __construct(MediaInputType $type, string $source, ?string $mimeType = null)
    {
        if ($source === '') {
            throw new \InvalidArgumentException('Media inputs require a non-empty source.');
        }

        $this->type = $type;
        $this->source = $source;
        $this->mimeType = $mimeType;
    }

    public function getType(): MediaInputType
    {
        return $this->type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function isFilePath(): bool
    {
        return $this->type === MediaInputType::FILE_PATH;
    }

    public function isUrl(): bool
    {
        return $this->type === MediaInputType::URL;
    }

    public function isBase64(): bool
    {
        return $this->type === MediaInputType::BASE64;
    }
}
