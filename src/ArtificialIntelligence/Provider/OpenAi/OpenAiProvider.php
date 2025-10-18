<?php

namespace App\ArtificialIntelligence\Provider\OpenAi;

use App\ArtificialIntelligence\Exception\OpenAiClientException;
use App\ArtificialIntelligence\Provider\AbstractAiProvider;
use App\ArtificialIntelligence\Provider\ProviderResponse;
use App\ArtificialIntelligence\Prompt\AudioToTextPrompt;
use App\ArtificialIntelligence\Prompt\ComputerVisionPrompt;
use App\ArtificialIntelligence\Prompt\ImageGenerationPrompt;
use App\ArtificialIntelligence\Prompt\MediaInput;
use App\ArtificialIntelligence\Prompt\MediaInputType;
use App\ArtificialIntelligence\Prompt\PromptInterface;
use App\ArtificialIntelligence\Prompt\PromptType;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\AudioToTextResult;
use App\ArtificialIntelligence\Result\ComputerVisionResult;
use App\ArtificialIntelligence\Result\GeneratedImage;
use App\ArtificialIntelligence\Result\GeneratedImageType;
use App\ArtificialIntelligence\Result\ImageGenerationResult;
use App\ArtificialIntelligence\Result\ResultInterface;
use App\ArtificialIntelligence\Result\TextResult;
use App\ArtificialIntelligence\Tool\ToolCall;
use App\ArtificialIntelligence\Tool\ToolDefinition;
use App\ArtificialIntelligence\Tool\ToolDefinitionType;
use JsonException;
use Symfony\Component\Mime\Part\DataPart;

final class OpenAiProvider extends AbstractAiProvider
{
    private const PROVIDER_NAME = 'openai';

    public function __construct(private readonly OpenAiClient $client)
    {
        parent::__construct(
            self::PROVIDER_NAME,
            PromptType::TEXT,
            PromptType::IMAGE_GENERATION,
            PromptType::COMPUTER_VISION,
            PromptType::AUDIO_TO_TEXT,
        );
    }

    public function process(PromptInterface $prompt): ProviderResponse
    {
        return match ($prompt->getType()) {
            PromptType::TEXT => $this->handleTextPrompt($prompt),
            PromptType::IMAGE_GENERATION => ProviderResponse::fromResult($this->handleImageGenerationPrompt($prompt)),
            PromptType::COMPUTER_VISION => ProviderResponse::fromResult($this->handleComputerVisionPrompt($prompt)),
            PromptType::AUDIO_TO_TEXT => ProviderResponse::fromResult($this->handleAudioToTextPrompt($prompt)),
        };
    }

    private function handleTextPrompt(TextPrompt $prompt): ProviderResponse
    {
        $messages = [];

        foreach ($prompt->getMessages() as $message) {
            $payloadMessage = [
                'role' => $message->getRole()->value,
                'content' => $message->getContent(),
            ];

            if ($message->getRole() === TextPromptRole::TOOL) {
                $metadata = $message->getMetadata();
                $toolCallId = isset($metadata['tool_call_id']) ? (string) $metadata['tool_call_id'] : null;

                if ($toolCallId === null || $toolCallId === '') {
                    throw new \InvalidArgumentException('Tool messages require a "tool_call_id" metadata entry for OpenAI.');
                }

                $payloadMessage['tool_call_id'] = $toolCallId;

                if (isset($metadata['tool_name']) && is_string($metadata['tool_name']) && $metadata['tool_name'] !== '') {
                    $payloadMessage['name'] = $metadata['tool_name'];
                }
            }

            $messages[] = $payloadMessage;
        }

        $metadata = $prompt->getMetadata();
        $model = $metadata['model'] ?? $this->client->getModel('text', 'gpt-5');
        unset($metadata['model']);

        $payload = [
            'model' => is_string($model) && $model !== '' ? $model : $this->client->getModel('text', 'gpt-5'),
            'messages' => $messages,
        ];

        if (($temperature = $prompt->getTemperature()) !== null) {
            $payload['temperature'] = $temperature;
        }

        if (($maxTokens = $prompt->getMaxOutputTokens()) !== null) {
            $payload['max_tokens'] = $maxTokens;
        }

        if ($metadata !== []) {
            $payload = array_merge($payload, $metadata);
        }

        $toolPayload = $this->buildToolPayload($prompt->getTools());

        if ($toolPayload !== []) {
            $payload['tools'] = $toolPayload;
        }

        $response = $this->client->chat($payload);
        $choice = $this->extractFirstChoice($response);

        $toolCalls = $this->extractToolCalls($choice);

        if ($toolCalls !== []) {
            return ProviderResponse::fromToolCalls($toolCalls, [
                'id' => $response['id'] ?? null,
                'model' => $response['model'] ?? null,
                'usage' => $response['usage'] ?? null,
                'finish_reason' => $choice['finish_reason'] ?? null,
            ]);
        }

        $content = $this->extractTextContent($response);

        $resultMetadata = [
            'id' => $response['id'] ?? null,
            'model' => $response['model'] ?? null,
            'usage' => $response['usage'] ?? null,
        ];

        $result = new TextResult(self::PROVIDER_NAME, $content, $resultMetadata);

        return ProviderResponse::fromResult($result, [
            'finish_reason' => $choice['finish_reason'] ?? null,
        ]);
    }

    /**
     * @param list<ToolDefinition> $tools
     * @return list<array<string, mixed>>
     */
    private function buildToolPayload(array $tools): array
    {
        if ($tools === []) {
            return [];
        }

        $payload = [];

        foreach ($tools as $tool) {
            $payload[] = $this->normaliseToolDefinition($tool);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function normaliseToolDefinition(ToolDefinition $tool): array
    {
        $parameters = $tool->getParameters();

        if ($parameters === []) {
            $parameters = [
                'type' => 'object',
                'properties' => (object) [],
            ];
        }

        $type = $tool->getType();

        if (!in_array($type, [ToolDefinitionType::FUNCTION, ToolDefinitionType::MCP], true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported tool definition type "%s" for OpenAI.', $type->value));
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $parameters,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array<string, mixed>
     */
    private function extractFirstChoice(array $response): array
    {
        $choices = $response['choices'] ?? null;

        if (!is_array($choices) || $choices === []) {
            throw OpenAiClientException::invalidResponse('Chat completion response must include at least one choice.');
        }

        $choice = $choices[0];

        if (!is_array($choice)) {
            throw OpenAiClientException::invalidResponse('Chat completion choice payload is malformed.');
        }

        return $choice;
    }

    /**
     * @param array<string, mixed> $choice
     *
     * @return list<ToolCall>
     */
    private function extractToolCalls(array $choice): array
    {
        $message = $choice['message'] ?? null;

        if (!is_array($message)) {
            return [];
        }

        $toolCalls = $message['tool_calls'] ?? null;

        if (!is_array($toolCalls) || $toolCalls === []) {
            return [];
        }

        $calls = [];

        foreach ($toolCalls as $toolCallData) {
            if (!is_array($toolCallData)) {
                continue;
            }

            $function = $toolCallData['function'] ?? null;

            if (!is_array($function)) {
                continue;
            }

            $name = isset($function['name']) ? (string) $function['name'] : '';

            if ($name === '') {
                continue;
            }

            $argumentsPayload = $function['arguments'] ?? [];
            $arguments = $this->decodeToolArguments($argumentsPayload);

            $calls[] = new ToolCall(
                $name,
                $arguments,
                isset($toolCallData['id']) ? (string) $toolCallData['id'] : null,
            );
        }

        return $calls;
    }

    /**
     * @param mixed $payload
     *
     * @return array<string, mixed>
     */
    private function decodeToolArguments(mixed $payload): array
    {
        if ($payload === null || $payload === '') {
            return [];
        }

        if (is_array($payload)) {
            return $payload;
        }

        if (!is_string($payload)) {
            throw OpenAiClientException::invalidResponse('Tool call arguments must be a JSON string or array.');
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw OpenAiClientException::invalidResponse('Unable to decode tool call arguments JSON.', $exception);
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function handleImageGenerationPrompt(ImageGenerationPrompt $prompt): ImageGenerationResult
    {
        $metadata = $prompt->getMetadata();
        $model = $metadata['model'] ?? $this->client->getModel('image_generation', 'gpt-5');
        unset($metadata['model']);

        $payload = [
            'model' => is_string($model) && $model !== '' ? $model : $this->client->getModel('image_generation', 'gpt-5'),
            'prompt' => $prompt->getDescription(),
        ];

        if ($prompt->getWidth() !== null && $prompt->getHeight() !== null) {
            $payload['size'] = sprintf('%dx%d', $prompt->getWidth(), $prompt->getHeight());
        }

        if ($prompt->getStylePreset() !== null) {
            $payload['style_preset'] = $prompt->getStylePreset();
        }

        if ($metadata !== []) {
            $payload = array_merge($payload, $metadata);
        }

        $response = $this->client->createImage($payload);

        $images = [];

        foreach ($response['data'] ?? [] as $index => $imageData) {
            if (isset($imageData['url'])) {
                $images[] = new GeneratedImage(
                    GeneratedImageType::URL,
                    (string) $imageData['url'],
                    $imageData['mime_type'] ?? null,
                );

                continue;
            }

            if (isset($imageData['b64_json'])) {
                $images[] = new GeneratedImage(
                    GeneratedImageType::BASE64,
                    (string) $imageData['b64_json'],
                    $imageData['mime_type'] ?? null,
                );
            }
        }

        if ($images === []) {
            throw OpenAiClientException::invalidResponse('Image generation returned no image payloads.');
        }

        $metadata = [
            'created' => $response['created'] ?? null,
            'revised_prompt' => $response['data'][0]['revised_prompt'] ?? null,
        ];

        return new ImageGenerationResult(self::PROVIDER_NAME, $images, $metadata);
    }

    private function handleComputerVisionPrompt(ComputerVisionPrompt $prompt): ComputerVisionResult
    {
        $content = [];

        if (($question = $prompt->getQuestion()) !== null) {
            $content[] = [
                'type' => 'text',
                'text' => $question,
            ];
        }

        $content[] = $this->createVisionImageContent($prompt->getImage());

        $metadata = $prompt->getMetadata();
        $model = $metadata['model'] ?? $this->client->getModel('vision', 'gpt-5');
        unset($metadata['model']);

        $payload = [
            'model' => is_string($model) && $model !== '' ? $model : $this->client->getModel('vision', 'gpt-5'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ];

        if (isset($metadata['max_tokens'])) {
            $payload['max_tokens'] = $metadata['max_tokens'];
            unset($metadata['max_tokens']);
        }

        if ($metadata !== []) {
            $payload = array_merge($payload, $metadata);
        }

        $response = $this->client->chat($payload);

        $text = $this->extractTextContent($response);

        $metadata = [
            'model' => $response['model'] ?? null,
            'usage' => $response['usage'] ?? null,
        ];

        return new ComputerVisionResult(self::PROVIDER_NAME, $text, $metadata);
    }

    private function handleAudioToTextPrompt(AudioToTextPrompt $prompt): AudioToTextResult
    {
        $dataPart = $this->createAudioDataPart($prompt->getAudio());

        $metadata = $prompt->getMetadata();
        $model = $metadata['model'] ?? $this->client->getModel('audio_to_text', 'gpt-5');
        unset($metadata['model']);

        $body = [
            'model' => is_string($model) && $model !== '' ? $model : $this->client->getModel('audio_to_text', 'gpt-5'),
            'file' => $dataPart,
            'response_format' => 'json',
        ];

        if (($language = $prompt->getLanguageCode()) !== null) {
            $body['language'] = $language;
        }

        if ($metadata !== []) {
            $body = array_merge($body, $metadata);
        }

        $response = $this->client->transcribe($body);

        $transcript = (string) ($response['text'] ?? $response['transcript'] ?? '');

        if ($transcript === '') {
            throw OpenAiClientException::invalidResponse('Audio transcription response did not include text.');
        }

        $metadata = [
            'duration' => $response['duration'] ?? null,
            'segments' => $response['segments'] ?? null,
        ];

        return new AudioToTextResult(self::PROVIDER_NAME, $transcript, $metadata);
    }

    private function extractTextContent(array $response): string
    {
        $choices = $response['choices'] ?? [];

        if ($choices === [] || !isset($choices[0]['message']['content'])) {
            throw OpenAiClientException::invalidResponse('Text response did not include any choices.');
        }

        $content = $choices[0]['message']['content'];

        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            $parts = [];

            foreach ($content as $part) {
                if (is_array($part) && isset($part['type'], $part['text']) && $part['type'] === 'text') {
                    $parts[] = (string) $part['text'];
                }
            }

            if ($parts !== []) {
                return implode("\n", $parts);
            }
        }

        throw OpenAiClientException::invalidResponse('Unable to extract text content from the OpenAI response.');
    }

    private function createVisionImageContent(MediaInput $media): array
    {
        return match ($media->getType()) {
            MediaInputType::URL => [
                'type' => 'image_url',
                'image_url' => ['url' => $media->getSource()],
            ],
            MediaInputType::BASE64 => [
                'type' => 'input_image',
                'image_base64' => $media->getSource(),
            ],
            MediaInputType::FILE_PATH => [
                'type' => 'input_image',
                'image_base64' => base64_encode($this->readFile($media->getSource())),
            ],
        };
    }

    private function createAudioDataPart(MediaInput $media): DataPart
    {
        $mimeType = $media->getMimeType() ?? $this->guessMimeType($media);

        return match ($media->getType()) {
            MediaInputType::FILE_PATH => $this->client->createDataPartFromPath($media->getSource(), $mimeType),
            MediaInputType::BASE64 => $this->client->createDataPartFromBinary(
                $this->decodeBase64($media->getSource()),
                $this->guessFilename($mimeType),
                $mimeType,
            ),
            MediaInputType::URL => $this->client->createDataPartFromBinary(
                $this->downloadBinaryFromUrl($media->getSource()),
                $this->guessFilename($mimeType),
                $mimeType,
            ),
        };
    }

    private function decodeBase64(string $payload): string
    {
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw OpenAiClientException::invalidResponse('Provided base64 media input could not be decoded.');
        }

        return $decoded;
    }

    private function readFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException(sprintf('Unable to read media file at path "%s".', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Failed to read media file at path "%s".', $path));
        }

        return $contents;
    }

    private function downloadBinaryFromUrl(string $url): string
    {
        $stream = $this->client->downloadToStream($url);

        $contents = stream_get_contents($stream);

        if ($contents === false) {
            throw new \RuntimeException(sprintf('Unable to read downloaded media from "%s".', $url));
        }

        fclose($stream);

        return $contents;
    }

    private function guessMimeType(MediaInput $media): string
    {
        if ($media->getMimeType() !== null) {
            return $media->getMimeType();
        }

        if ($media->getType() === MediaInputType::FILE_PATH) {
            $mime = mime_content_type($media->getSource());

            if (is_string($mime)) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    private function guessFilename(string $mimeType): string
    {
        $extensions = [
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/wave' => 'wav',
            'audio/wav' => 'wav',
            'audio/x-wav' => 'wav',
            'audio/flac' => 'flac',
            'audio/ogg' => 'ogg',
        ];

        $extension = $extensions[strtolower($mimeType)] ?? 'bin';

        return sprintf('upload.%s', $extension);
    }
}
