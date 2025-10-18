<?php

namespace App\Tests\ArtificialIntelligence\Provider\OpenAi;

use App\ArtificialIntelligence\Provider\OpenAi\OpenAiClient;
use App\ArtificialIntelligence\Provider\OpenAi\OpenAiProvider;
use App\ArtificialIntelligence\Prompt\AudioToTextPrompt;
use App\ArtificialIntelligence\Prompt\ComputerVisionPrompt;
use App\ArtificialIntelligence\Prompt\ImageGenerationPrompt;
use App\ArtificialIntelligence\Prompt\MediaInput;
use App\ArtificialIntelligence\Prompt\MediaInputType;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Result\AudioToTextResult;
use App\ArtificialIntelligence\Result\ComputerVisionResult;
use App\ArtificialIntelligence\Result\ImageGenerationResult;
use App\ArtificialIntelligence\Result\TextResult;
use App\ArtificialIntelligence\Tool\ToolDefinition;
use App\ArtificialIntelligence\Tool\ToolDefinitionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenAiProviderTest extends TestCase
{
    public function testProcessTextPromptReturnsTextResult(): void
    {
        $captured = null;

        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'options' => $options];

            $payload = [
                'id' => 'chatcmpl-test',
                'model' => 'gpt-5',
                'choices' => [
                    [
                        'message' => ['content' => 'Hello back!'],
                    ],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 12],
            ];

            return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
        });

        $client = new OpenAiClient($httpClient, 'test-api-key', ['api_base' => 'https://api.openai.com/v1', 'models' => ['text' => 'gpt-5']]);
        $provider = new OpenAiProvider($client);

        $prompt = new TextPrompt([
            new TextPromptMessage(TextPromptRole::USER, 'Hello?'),
        ]);

        $response = $provider->process($prompt);

        self::assertTrue($response->hasResult());
        $result = $response->getResult();
        self::assertInstanceOf(TextResult::class, $result);
        self::assertSame('openai', $result->getProviderName());
        self::assertSame('Hello back!', $result->getContent());

        self::assertNotNull($captured);
        self::assertSame('POST', $captured['method']);
        self::assertStringContainsString('chat/completions', $captured['url']);
        $requestBody = json_decode($captured['options']['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('gpt-5', $requestBody['model']);
        self::assertSame('Hello?', $requestBody['messages'][0]['content']);
    }

    public function testProcessImagePromptReturnsImages(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            $payload = [
                'created' => 1,
                'data' => [
                    [
                        'b64_json' => base64_encode('fake-image'),
                    ],
                ],
            ];

            return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
        });

        $client = new OpenAiClient($httpClient, 'test-api-key', ['api_base' => 'https://api.openai.com/v1', 'models' => ['image_generation' => 'gpt-5']]);
        $provider = new OpenAiProvider($client);

        $prompt = new ImageGenerationPrompt('Draw a friendly assistant', 512, 512, 'vivid');
        $response = $provider->process($prompt);

        self::assertTrue($response->hasResult());
        $result = $response->getResult();
        self::assertInstanceOf(ImageGenerationResult::class, $result);
        self::assertCount(1, $result->getImages());
        self::assertSame('openai', $result->getProviderName());
        self::assertSame($result->getImages()[0]->getValue(), base64_encode('fake-image'));
    }

    public function testTextPromptRespectsMetadataModelOverride(): void
    {
        $captured = null;

        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'options' => $options];

            $payload = [
                'id' => 'chatcmpl-test',
                'model' => 'gpt-5-creative',
                'choices' => [
                    [
                        'message' => ['content' => 'Creative reply'],
                    ],
                ],
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 8],
            ];

            return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
        });

        $client = new OpenAiClient($httpClient, 'test-api-key', ['api_base' => 'https://api.openai.com/v1', 'models' => ['text' => 'gpt-5']]);
        $provider = new OpenAiProvider($client);

        $prompt = new TextPrompt([
            new TextPromptMessage(TextPromptRole::SYSTEM, 'You are a creative assistant.'),
            new TextPromptMessage(TextPromptRole::USER, 'Tell me a story.'),
        ], null, null, ['model' => 'gpt-5-creative', 'max_tokens' => 256]);

        $response = $provider->process($prompt);

        self::assertTrue($response->hasResult());
        $result = $response->getResult();
        self::assertInstanceOf(TextResult::class, $result);
        self::assertSame('Creative reply', $result->getContent());

        $requestBody = json_decode($captured['options']['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('gpt-5-creative', $requestBody['model']);
        self::assertSame(256, $requestBody['max_tokens']);
    }

    public function testProcessVisionPromptReturnsText(): void
    {
        $httpClient = new MockHttpClient(static function (): MockResponse {
            $payload = [
                'model' => 'gpt-5',
                'choices' => [
                    [
                        'message' => ['content' => 'The chart shows positive growth.'],
                    ],
                ],
                'usage' => ['prompt_tokens' => 42, 'completion_tokens' => 24],
            ];

            return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
        });

        $client = new OpenAiClient($httpClient, 'test-api-key', ['api_base' => 'https://api.openai.com/v1']);
        $provider = new OpenAiProvider($client);

        $image = new MediaInput(MediaInputType::BASE64, base64_encode('fake-image-bytes'), 'image/png');
        $prompt = new ComputerVisionPrompt($image, 'What does this chart show?');

        $response = $provider->process($prompt);

        self::assertTrue($response->hasResult());
        $result = $response->getResult();
        self::assertInstanceOf(ComputerVisionResult::class, $result);
        self::assertSame('The chart shows positive growth.', $result->getContent());
    }

    public function testTextPromptWithToolCallsRequestsToolExecution(): void
    {
        $captured = null;

        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'options' => $options];

            $payload = [
                'id' => 'chatcmpl-tool',
                'model' => 'gpt-5',
                'choices' => [
                    [
                        'message' => [
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call-abc',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'get_weather',
                                        'arguments' => json_encode(['location' => 'Paris'], JSON_THROW_ON_ERROR),
                                    ],
                                ],
                            ],
                        ],
                        'finish_reason' => 'tool_calls',
                    ],
                ],
                'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 0],
            ];

            return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
        });

        $client = new OpenAiClient($httpClient, 'test-api-key', ['api_base' => 'https://api.openai.com/v1']);
        $provider = new OpenAiProvider($client);

        $tool = new ToolDefinition(
            'get_weather',
            'Fetch current weather conditions.',
            [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string'],
                ],
                'required' => ['location'],
            ],
            ToolDefinitionType::FUNCTION,
        );

        $prompt = new TextPrompt([
            new TextPromptMessage(TextPromptRole::USER, 'What is the weather in Paris?'),
        ], null, null, [], [$tool]);

        $response = $provider->process($prompt);

        self::assertFalse($response->hasResult());
        self::assertTrue($response->isToolContinuation());

        $toolCalls = $response->getToolCalls();
        self::assertCount(1, $toolCalls);

        $call = $toolCalls[0];
        self::assertSame('get_weather', $call->getName());
        self::assertSame('call-abc', $call->getCallId());
        self::assertSame(['location' => 'Paris'], $call->getArguments());

        self::assertNotNull($captured);
        $requestBody = json_decode($captured['options']['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('tools', $requestBody);
        self::assertCount(1, $requestBody['tools']);
        self::assertSame('get_weather', $requestBody['tools'][0]['function']['name']);
    }

    public function testProcessAudioPromptReturnsTranscript(): void
    {
        $captured = null;

        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$captured): MockResponse {
            $captured = ['method' => $method, 'url' => $url, 'options' => $options];

            $payload = [
                'text' => 'Hello world transcript',
                'duration' => 3.2,
            ];

            return new MockResponse(json_encode($payload, JSON_THROW_ON_ERROR));
        });

        $client = new OpenAiClient($httpClient, 'test-api-key', ['api_base' => 'https://api.openai.com/v1', 'models' => ['audio_to_text' => 'gpt-5']]);
        $provider = new OpenAiProvider($client);

        $audioData = base64_encode('fake-audio');
        $prompt = new AudioToTextPrompt(new MediaInput(MediaInputType::BASE64, $audioData, 'audio/wav'), 'en');

        $response = $provider->process($prompt);

        self::assertTrue($response->hasResult());
        $result = $response->getResult();
        self::assertInstanceOf(AudioToTextResult::class, $result);
        self::assertSame('Hello world transcript', $result->getTranscript());

        self::assertNotNull($captured);
        self::assertArrayHasKey('body', $captured['options']);
        self::assertTrue(is_callable($captured['options']['body']) || is_iterable($captured['options']['body']));
        self::assertArrayHasKey('headers', $captured['options']);
        self::assertStringContainsString('multipart/form-data', implode(';', $captured['options']['headers']));
    }
}
