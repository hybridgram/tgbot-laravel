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

final class SetWebhookCommand extends Command
{
    protected $signature = 'hybridgram:webhook:set {--bot=main}';

    protected $description = 'Set webhook URL for Telegram bot';

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
        

        $url = $webhookConfig->url ?? route('telegram.bot.webhook', ['botId' => $botId]);
        

        if ($webhookConfig->port !== null && $webhookConfig->port !== 0) {
            $parsedUrl = parse_url($url);
            if ($parsedUrl !== false) {
                $scheme = $parsedUrl['scheme'] ?? 'https';
                $host = $parsedUrl['host'] ?? '';
                $path = $parsedUrl['path'] ?? '';
                $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
                $url = "{$scheme}://{$host}:{$webhookConfig->port}{$path}{$query}";
            }
        }


        $allowedUpdates = !empty($webhookConfig->allowedUpdates) ? $webhookConfig->allowedUpdates : null;

        $dropPendingUpdates = $webhookConfig->dropPendingUpdates ? true : null;

        $result = $telegram->setWebhook(
            $url,
            $webhookConfig->ipAddress,
            null,
            $allowedUpdates,
            $dropPendingUpdates,
            $webhookConfig->secretToken
        );
        
        if ($result instanceof FailResult) {
            $this->error("Failed to set webhook: {$result->response->body}");
        } else {
            $this->info("Webhook set successfully for bot: {$botId}");
            $this->line("URL: {$url}");
            if ($webhookConfig->ipAddress) {
                $this->line("IP Address: {$webhookConfig->ipAddress}");
            }
            if ($allowedUpdates !== null) {
                $this->line("Allowed Updates: " . implode(', ', $allowedUpdates));
            }
            if ($webhookConfig->dropPendingUpdates) {
                $this->line("Drop Pending Updates: enabled");
            }
            if ($webhookConfig->secretToken) {
                $this->line("Secret Token: configured");
            }
        }
    }
}
