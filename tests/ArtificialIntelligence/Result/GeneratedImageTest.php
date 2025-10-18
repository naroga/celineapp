<?php

namespace App\Tests\ArtificialIntelligence\Result;

use App\ArtificialIntelligence\Result\GeneratedImage;
use App\ArtificialIntelligence\Result\GeneratedImageType;
use PHPUnit\Framework\TestCase;

final class GeneratedImageTest extends TestCase
{
    public function testBase64ImageFallsBackToPngMimeType(): void
    {
        $image = new GeneratedImage(GeneratedImageType::BASE64, 'Zm9v', null);

        self::assertSame('image/png', $image->getMimeType());
    }

    public function testUrlImageDoesNotForceMimeType(): void
    {
        $image = new GeneratedImage(GeneratedImageType::URL, 'https://example.com/image.png');

        self::assertNull($image->getMimeType());
    }
}
