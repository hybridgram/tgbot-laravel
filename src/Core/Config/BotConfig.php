<?php

declare(strict_types=1);

namespace HybridGram\Core\Config;

use HybridGram\Core\UpdateMode\UpdateModeEnum;
use HybridGram\Exceptions\UpdateModeDoesntSet;
use SensitiveParameter;

final class BotConfig
{
    public ?PollingModeConfig $pollingConfig;

    public ?WebhookModeConfig $webhookConfig;

    /**
     */
    public function __construct(
        #[SensitiveParameter]
        public readonly string $token,
        public readonly string $botId,
        public readonly UpdateModeEnum $updateMode,
        public string $routesPath,
        ?PollingModeConfig $pollingConfig = null,
        ?WebhookModeConfig $webhookConfig = null,
        public readonly ?string $botName = null,
    ) {
        $this->pollingConfig = $pollingConfig;
        $this->webhookConfig = $webhookConfig;

        if ($updateMode === UpdateModeEnum::POLLING && !$pollingConfig) {
            $this->pollingConfig = new PollingModeConfig();
        }

        if ($updateMode === UpdateModeEnum::WEBHOOK && !$webhookConfig) {
            $this->webhookConfig = new WebhookModeConfig();
        }
    }

    public function getUpdateMode(): UpdateModeEnum
    {
        return $this->updateMode;
    }

    public static function getBotConfig(string $botId): ?BotConfig
    {
        $bots = config('hybridgram.bots', []);

        foreach ($bots as $bot) {
            if ($bot['bot_id'] === $botId) {
                $pollingConfig = $bot['update_mode'] === UpdateModeEnum::POLLING
                    ? new PollingModeConfig(
                        $bot['polling_limit'],
                        $bot['allowed_updates'],
                        $bot['polling_timeout'],
                    ) : null;
                $webhookConfig = in_array($bot['update_mode'], [UpdateModeEnum::WEBHOOK, UpdateModeEnum::QUEUE])
                    ? new WebhookModeConfig(
                        $bot['update_mode'] === UpdateModeEnum::QUEUE ? $bot['webhook_url'] : route('telegram.bot.webhook', ['botId' => $botId]),
                            $bot['webhook_port'],
                            $bot['certificate_path'],
                            $bot['ip_address'],
                            $bot['allowed_updates'],
                            $bot['webhook_drop_pending_updates'],
                            $bot['secret_token'],
                    ) : null;

                return new BotConfig(
                    $bot['token'],
                    $bot['bot_id'],
                    $bot['update_mode'],
                    $bot['routes_file'],
                    $pollingConfig,
                    $webhookConfig,
                    $bot['bot_name']
                );
            }
        }

        return null;
    }
}
