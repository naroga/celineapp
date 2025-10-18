<?php

namespace App\Command;

use App\ArtificialIntelligence\AiGateway;
use App\ArtificialIntelligence\Prompt\TextPrompt;
use App\ArtificialIntelligence\Prompt\TextPromptMessage;
use App\ArtificialIntelligence\Prompt\TextPromptRole;
use App\ArtificialIntelligence\Provider\AiProviderRegistry;
use App\ArtificialIntelligence\Provider\AiProviderResolver;
use App\ArtificialIntelligence\Result\TextResult;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ai:chat', description: 'Interactively chat with a configured AI provider.')]
final class AiChatCommand extends Command
{
    public function __construct(
        private readonly AiGateway $gateway,
        private readonly AiProviderRegistry $registry,
        private readonly AiProviderResolver $resolver,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $providers = array_keys($this->registry->all());

        if ($providers === []) {
            $io->error('No AI providers are registered. Check your configuration.');

            return Command::FAILURE;
        }

        $providerName = $io->choice('Select provider', $providers, $providers[0]);

        $config = $this->resolver->getProviderConfiguration($providerName);
        $availableModels = array_values(array_unique(array_filter($config['models'] ?? [], static fn ($model) => is_string($model) && $model !== '')));

        if ($availableModels === []) {
            $defaultModel = $config['models']['text'] ?? 'gpt-5';
            $model = (string) $io->ask('Enter the model identifier', $defaultModel, static function (?string $answer) {
                $trimmed = trim((string) $answer);

                if ($trimmed === '') {
                    throw new \RuntimeException('Please provide a model identifier.');
                }

                return $trimmed;
            });
        } else {
            $choices = [...$availableModels, '(custom)'];
            $selection = $io->choice('Select model', $choices, $choices[0]);

            if ($selection === '(custom)') {
                $model = (string) $io->ask('Enter the model identifier', $availableModels[0], static function (?string $answer) {
                    $trimmed = trim((string) $answer);

                    if ($trimmed === '') {
                        throw new \RuntimeException('Please provide a model identifier.');
                    }

                    return $trimmed;
                });
            } else {
                $model = $selection;
            }
        }

        $systemPrompt = $io->ask('Optional system prompt (empty to skip)');

        $temperature = $io->ask('Temperature (0.0 - 2.0, empty for provider default)', null, static function (?string $answer) {
            if ($answer === null || trim($answer) === '') {
                return null;
            }

            if (!is_numeric($answer)) {
                throw new \RuntimeException('Temperature must be numeric.');
            }

            $value = (float) $answer;

            if ($value < 0.0 || $value > 2.0) {
                throw new \RuntimeException('Temperature must be between 0.0 and 2.0.');
            }

            return $value;
        });

        $io->writeln('Type your message and press enter. Use :quit to leave the session.');

        $messages = [];

        if ($systemPrompt !== null && trim($systemPrompt) !== '') {
            $messages[] = new TextPromptMessage(TextPromptRole::SYSTEM, $systemPrompt);
        }

        while (true) {
            $inputMessage = $io->ask('You', null, static function (?string $answer) {
                if ($answer === null) {
                    return null;
                }

                $trimmed = trim($answer);

                return $trimmed === '' ? null : $trimmed;
            });

            if ($inputMessage === null) {
                $io->writeln('<comment>Empty message skipped. Use :quit to exit.</comment>');

                continue;
            }

            if ($inputMessage === ':quit') {
                break;
            }

            $messages[] = new TextPromptMessage(TextPromptRole::USER, $inputMessage);

            try {
                $prompt = new TextPrompt($messages, $temperature, null, ['model' => $model]);
                $result = $this->gateway->execute($prompt, providerName: $providerName);

                if (!$result instanceof TextResult) {
                    $io->warning(sprintf('Provider returned a %s response; only text is supported in this CLI.', $result->getType()->value));

                    continue;
                }

                $assistantMessage = $result->getContent();
                $messages[] = new TextPromptMessage(TextPromptRole::ASSISTANT, $assistantMessage);

                $io->writeln(sprintf('<info>%s:</info> %s', ucfirst($providerName), $assistantMessage));

                $metadata = $result->getMetadata();

                if (isset($metadata['usage']['prompt_tokens'], $metadata['usage']['completion_tokens'])) {
                    $io->writeln(sprintf(
                        '<comment>Usage:</comment> prompt %d, completion %d, total %d tokens',
                        (int) $metadata['usage']['prompt_tokens'],
                        (int) $metadata['usage']['completion_tokens'],
                        (int) (($metadata['usage']['prompt_tokens'] ?? 0) + ($metadata['usage']['completion_tokens'] ?? 0)),
                    ));
                }
            } catch (\Throwable $exception) {
                $io->error(sprintf('LLM request failed: %s', $exception->getMessage()));

                return Command::FAILURE;
            }
        }

        $io->success('Conversation ended.');

        return Command::SUCCESS;
    }
}
