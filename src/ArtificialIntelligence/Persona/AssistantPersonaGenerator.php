<?php

namespace App\ArtificialIntelligence\Persona;

use App\ArtificialIntelligence\AiGateway;
use App\ArtificialIntelligence\Prompt\ImageGenerationPrompt;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\GeneratedImage;
use App\ArtificialIntelligence\Result\ImageGenerationResult;
use App\ArtificialIntelligence\Result\TextResult;

final class AssistantPersonaGenerator
{
    private const SUPPORTED_GENDERS = [
        'male',
        'female',
    ];

    private const PROFILE_IMAGE_MODEL = 'gpt-image-1';

    public function __construct(private readonly AiGateway $gateway)
    {
    }

    public function generateName(string $gender): string
    {
        $normalizedGender = $this->normalizeGender($gender);

            $prompt = new TextPrompt([
                new TextPromptMessage(
                    TextPromptRole::SYSTEM,
                    'You generate professional first names for human support assistants. '
                    . 'Respond with a single name matching the requested gender. '
                    . 'Do not include punctuation, titles, or explanations—just the name.',
                ),
                new TextPromptMessage(
                    TextPromptRole::USER,
                    sprintf('Provide a %s first name suitable for a friendly professional assistant.', $normalizedGender),
                ),
            ]);

        $result = $this->gateway->execute($prompt);

        if (!$result instanceof TextResult) {
            throw new \RuntimeException('AI provider returned an unexpected result while generating a name.');
        }

        return $this->extractName($result);
    }

    public function generateProfilePicture(string $name, string $gender): GeneratedImage
    {
        $normalizedGender = $this->normalizeGender($gender);
        $trimmedName = $this->normalizeName($name);

        $description = sprintf(
            'Ultra-realistic portrait photograph of a %s professional support assistant named %s, smiling warmly,'
            . ' wearing a sleek headset with microphone, seated in a tidy home office with natural light. '
            . 'Emphasize approachability, clarity, and a modern remote workspace vibe.',
            $normalizedGender,
            $trimmedName,
        );

        $prompt = new ImageGenerationPrompt($description, null, null, null, [
            'n' => 1,
            'model' => self::PROFILE_IMAGE_MODEL,
            'size' => '1024x1024',
        ]);

        $result = $this->gateway->execute($prompt);

        if (!$result instanceof ImageGenerationResult) {
            throw new \RuntimeException('AI provider returned an unexpected result while generating a profile picture.');
        }

        $images = $result->getImages();

        if ($images === []) {
            throw new \RuntimeException('Image generation returned no images.');
        }

        return $images[0];
    }

    private function normalizeGender(string $gender): string
    {
        $normalized = mb_strtolower(trim($gender));

        $aliases = [
            'male' => ['male', 'man', 'm'],
            'female' => ['female', 'woman', 'f'],
        ];

        foreach ($aliases as $canonical => $options) {
            if (in_array($normalized, $options, true)) {
                return $canonical;
            }
        }

        throw new \InvalidArgumentException('Unsupported gender option.');
    }

    private function extractName(TextResult $result): string
    {
        $content = trim($result->getContent());

        if ($content === '') {
            throw new \RuntimeException('AI provider returned an empty name result.');
        }

        $firstLine = trim(preg_split('/\R/', $content)[0]);
        $firstToken = preg_split('/\s+/', $firstLine)[0] ?? '';
        $sanitized = preg_replace("/[^\p{L}\-']+/u", '', $firstToken);

        if ($sanitized === null || $sanitized === '') {
            throw new \RuntimeException('AI provider returned an invalid name format.');
        }

        return $this->normalizeName($sanitized);
    }

    private function normalizeName(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new \RuntimeException('A name is required to generate a profile picture.');
        }

        return mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
    }
}
