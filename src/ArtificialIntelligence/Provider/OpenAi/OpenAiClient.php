<?php

namespace App\ArtificialIntelligence\Provider\OpenAi;

use App\ArtificialIntelligence\Exception\OpenAiClientException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\RedirectionException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class OpenAiClient
{
    private readonly HttpClientInterface $httpClient;
    private readonly array $baseHeaders;

    /**
     * @var array<string, mixed>
     */
    private readonly array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        HttpClientInterface $httpClient,
        #[Autowire('%env(string:OPENAI_API_KEY)%')]
        private readonly string $apiKey,
        #[Autowire('%app.ai.providers.providers.openai%')]
        array $config = [],
    ) {
        if ($this->apiKey === '') {
            throw OpenAiClientException::missingApiKey();
        }

        $baseUri = rtrim($config['api_base'] ?? 'https://api.openai.com/v1', '/') . '/';

        $this->baseHeaders = array_filter([
            'Authorization' => sprintf('Bearer %s', $this->apiKey),
            'OpenAI-Organization' => $config['organization'] ?? null,
        ]);

        $this->httpClient = $httpClient->withOptions([
            'base_uri' => $baseUri,
            'headers' => $this->baseHeaders,
            'timeout' => 60.0,
        ]);

        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function chat(array $payload): array
    {
        return $this->requestJson('POST', 'chat/completions', [
            'json' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createImage(array $payload): array
    {
        return $this->requestJson('POST', 'images/generations', [
            'json' => $payload,
        ]);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    public function transcribe(array $body): array
    {
        $formData = new FormDataPart($body);

        return $this->requestJson('POST', 'audio/transcriptions', [
            'headers' => array_merge($this->baseHeaders, $formData->getPreparedHeaders()->toArray()),
            'body' => $formData->bodyToIterable(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getModel(string $capability, ?string $fallback = null): string
    {
        $models = $this->config['models'] ?? [];

        return $models[$capability] ?? $models['text'] ?? $fallback ?? 'gpt-5';
    }

    public function getContextWindow(): ?int
    {
        $window = $this->config['context_window'] ?? null;

        return is_int($window) ? $window : null;
    }

    public function buildVisionContentFromImage(string $data, ?string $mimeType = null): array
    {
        if ($mimeType !== null && str_starts_with($mimeType, 'image/')) {
            return [
                'type' => 'input_image',
                'image_base64' => $data,
            ];
        }

        return [
            'type' => 'image_url',
            'image_url' => ['url' => $data],
        ];
    }

    public function createDataPartFromBinary(string $binary, string $filename, ?string $mimeType = null): DataPart
    {
        return new DataPart($binary, $filename, $mimeType ?? 'application/octet-stream');
    }

    public function createDataPartFromPath(string $path, ?string $mimeType = null): DataPart
    {
        return DataPart::fromPath($path, null, $mimeType);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $path, array $options): array
    {
        try {
            $response = $this->httpClient->request($method, ltrim($path, '/'), $options);
        } catch (TransportExceptionInterface $exception) {
            throw OpenAiClientException::network($exception);
        }

        return $this->decodeResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        try {
            $status = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (RedirectionException | ClientException | ServerException $exception) {
            throw OpenAiClientException::network($exception);
        }

        if ($status >= 400) {
            $message = $this->extractErrorMessage($content);

            throw OpenAiClientException::fromResponse($status, $message);
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw OpenAiClientException::invalidResponse('Unable to decode JSON payload.', $exception);
        }

        return $decoded;
    }

    private function extractErrorMessage(string $content): string
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $error = $decoded['error'] ?? null;

            if (is_array($error) && isset($error['message'])) {
                return (string) $error['message'];
            }

            if (isset($decoded['message'])) {
                return (string) $decoded['message'];
            }
        } catch (\Throwable) {
            // Ignore decoding failures; we'll fall back to generic message.
        }

        return trim($content) !== '' ? $content : 'Unknown error';
    }

    /**
     * @return resource
     */
    public function downloadToStream(string $url)
    {
        try {
            $response = HttpClient::create()->request('GET', $url);
            $status = $response->getStatusCode();
            if ($status >= 400) {
                throw OpenAiClientException::fromResponse($status, sprintf('Unable to download media from "%s".', $url));
            }

            $stream = fopen('php://temp', 'w+b');

            if ($stream === false) {
                throw new \RuntimeException('Unable to open temporary stream.');
            }

            foreach ($response->toStream() as $chunk) {
                fwrite($stream, $chunk->getContent());
            }

            rewind($stream);

            return $stream;
        } catch (TransportExceptionInterface $exception) {
            throw OpenAiClientException::network($exception);
        }
    }
}
