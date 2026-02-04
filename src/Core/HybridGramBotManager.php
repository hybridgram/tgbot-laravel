<?php

declare(strict_types=1);

namespace HybridGram\Core;

use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\UpdateMode\PollingUpdateMode;
use HybridGram\Core\UpdateMode\UpdateModeEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\Update\WebhookInfo;

final class HybridGramBotManager
{
    /**
     * @var BotConfig[]
     */
    private array $botConfigs = [];

    private bool $initialized = false;

    private ?Command $command = null;

    public function run(?string $botId = null): void
    {
        if ($botId !== null) {
            $botConfig = BotConfig::getBotConfig($botId);
            if ($botConfig && $botConfig->getUpdateMode() === UpdateModeEnum::POLLING) {
                $this->checkWebhookBeforePolling($botConfig);
                $pollingMode = new PollingUpdateMode($botConfig);
                $pollingMode->setCommand($this->command);
                $pollingMode->run();
            }

            if ($botConfig === null) {
                throw new \RuntimeException("No bot configuration found for bot id '{$botId}'. Check hybridgram.bots config.");
            }

            throw new \RuntimeException(
                "Bot '{$botId}' is not configured for polling (update_mode is not 'polling'). ".
                'To use polling, set update_mode to "polling" in config.'
            );
        }

    }

    public function getPollingRunStrategy(): PollingRunStrategy
    {
        $configs = $this->getPollingBotConfigs();

        if ($configs === []) {
            return PollingRunStrategy::none();
        }

        if (count($configs) === 1) {
            return PollingRunStrategy::single($configs[0]);
        }

        return PollingRunStrategy::multiple($configs);
    }

    /**
     * @param  BotConfig[]  $botConfigs
     */
    public function withBots(array $botConfigs): HybridGramBotManager
    {
        foreach ($botConfigs as $botConfig) {
            $this->addBotIfNotExists($botConfig);
        }

        return $this;
    }

    public function withBot(BotConfig $botConfig): HybridGramBotManager
    {
        $this->addBotIfNotExists($botConfig);

        return $this;
    }

    public function setCommand(?Command $command): HybridGramBotManager
    {
        $this->command = $command;

        return $this;
    }

    private function addBotIfNotExists(BotConfig $botConfig): void
    {
        if (array_any($this->botConfigs, fn ($existingConfig) => $existingConfig->botId === $botConfig->botId)) {
            return;
        }

        $this->botConfigs[] = $botConfig;
    }

    /**
     * @return BotConfig[]
     */
    public function getBotConfigs(): array
    {
        return $this->botConfigs;
    }

    /**
     * Initializes from config and returns only bot configs with POLLING mode.
     * Used by the polling command to decide single process vs multiple subprocesses.
     *
     * @return BotConfig[]
     */
    public function getPollingBotConfigs(): array
    {
        $this->initializeBots();

        return array_values(array_filter(
            $this->botConfigs,
            fn (BotConfig $c) => $c->getUpdateMode() === UpdateModeEnum::POLLING
        ));
    }

    private function initializeBots(): void
    {
        if ($this->initialized) {
            return;
        }

        $bots = config('hybridgram.bots', []);
        foreach ($bots as $botConfig) {
            $config = BotConfig::getBotConfig($botConfig['bot_id']);
            if ($config) {
                $this->addBotIfNotExists($config);
            }
        }

        $this->initialized = true;
    }

    private function checkWebhookBeforePolling(BotConfig $botConfig): void
    {
        $botApi = new TelegramBotApi($botConfig->token);

        $webhookInfo = $botApi->getWebhookInfo();

        if ($webhookInfo instanceof FailResult) {
            return;
        }

        /** @var WebhookInfo $webhookInfo */
        if (! empty($webhookInfo->url)) {
            $isConsole = App::runningInConsole();

            if ($isConsole && $this->command !== null) {
                $this->command->warn("Webhook is active for bot '{$botConfig->botId}' (URL: {$webhookInfo->url})");
                $this->command->warn('Polling mode requires webhook to be deleted.');

                if ($this->command->confirm('Do you want to delete the webhook?', true)) {
                    $deleteResult = $botApi->deleteWebhook();

                    if ($deleteResult instanceof FailResult) {
                        $this->command->error("Failed to delete webhook: {$deleteResult->description}");
                        throw new \RuntimeException('Cannot start polling: webhook is active and deletion failed');
                    }

                    $this->command->info("Webhook deleted successfully for bot '{$botConfig->botId}'");
                } else {
                    throw new \RuntimeException("Cannot start polling: webhook is active. Please delete it first using: php artisan react_telegram:webhook:delete --bot={$botConfig->botId}");
                }
            } else {
                throw new \RuntimeException("Cannot start polling for bot '{$botConfig->botId}': webhook is active (URL: {$webhookInfo->url}). Please delete it first using: php artisan react_telegram:webhook:delete --bot={$botConfig->botId}");
            }
        }
    }
}
