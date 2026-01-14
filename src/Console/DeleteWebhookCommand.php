<?php

declare(strict_types=1);

namespace HybridGram\Console;

use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\Config\WebhookModeConfig;
use HybridGram\Telegram\Sender\OutgoingDispatcherInterface;
use HybridGram\Telegram\TelegramBotApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Phptg\BotApi\FailResult;

final class DeleteWebhookCommand extends Command
{
    protected $signature = 'hybridgram:webhook:delete {--bot=main}';

    protected $description = 'Delete webhook for Telegram bot';

    public function handle(): void
    {
        $botId = $this->option('bot');
        $config = BotConfig::getBotConfig($botId);
        
        if ($config === null) {
            $this->error("Bot config not found for bot: {$botId}");
            return;
        }

        $dispatcher = App::make(OutgoingDispatcherInterface::class);
        $telegram = (new TelegramBotApi($config->token, 'https://api.telegram.org', null, $dispatcher))->withBotId($botId);

        $webhookConfig = $config->webhookConfig ?? new WebhookModeConfig();
        $dropPendingUpdates = $webhookConfig->dropPendingUpdates ? true : null;

        $result = $telegram->deleteWebhook($dropPendingUpdates);

        if ($result instanceof FailResult) {
            $this->error("Failed to delete webhook for bot {$botId}: {$result->response->body}");
        } else {
            $this->info("Webhook deleted successfully for bot: {$botId}");
            if ($dropPendingUpdates) {
                $this->line("Pending updates were dropped");
            }
        }
    }
}
