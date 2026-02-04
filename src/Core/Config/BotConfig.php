<?php

declare(strict_types=1);

namespace HybridGram\Core\Config;

use HybridGram\Core\UpdateMode\UpdateModeEnum;
use SensitiveParameter;

final class BotConfig
{
    public ?PollingModeConfig $pollingConfig;

    public ?WebhookModeConfig $webhookConfig;

    public function __construct(
        #[SensitiveParameter]
        public readonly string $token,
        public readonly string $botId,
        public string|UpdateModeEnum $updateMode,
        public string $routesPath,
        ?PollingModeConfig $pollingConfig = null,
        ?WebhookModeConfig $webhookConfig = null,
        public readonly ?string $botName = null,
    ) {
        $this->pollingConfig = $pollingConfig;
        $this->webhookConfig = $webhookConfig;

        if ($updateMode === UpdateModeEnum::POLLING && ! $pollingConfig) {
            $this->pollingConfig = new PollingModeConfig;
        }

        if ($updateMode === UpdateModeEnum::WEBHOOK && ! $webhookConfig) {
            $this->webhookConfig = new WebhookModeConfig;
        }
    }

    public function getUpdateMode(): UpdateModeEnum
    {
        return $this->updateMode instanceof UpdateModeEnum ? $this->updateMode : UpdateModeEnum::from($this->updateMode);
    }

    public static function getBotConfig(string $botId): ?BotConfig
    {
        $bots = config('hybridgram.bots', []);

        foreach ($bots as $bot) {
            if ($bot['bot_id'] === $botId) {
                $updateMode = $bot['update_mode'] instanceof UpdateModeEnum
                    ? $bot['update_mode']
                    : UpdateModeEnum::from($bot['update_mode']);

                $pollingConfig = $updateMode === UpdateModeEnum::POLLING
                    ? new PollingModeConfig(
                        $bot['polling_limit'],
                        $bot['allowed_updates'],
                        $bot['polling_timeout'],
                    ) : null;
                $webhookConfig = in_array($updateMode, [UpdateModeEnum::WEBHOOK, UpdateModeEnum::WEBHOOK_ASYNC], true)
                    ? new WebhookModeConfig(
                        $updateMode === UpdateModeEnum::WEBHOOK_ASYNC ? $bot['webhook_url'] : route('telegram.bot.webhook', ['botId' => $botId]),
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
                    $updateMode,
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
