<?php

declare(strict_types=1);

namespace HybridGram\Console;

use HybridGram\Core\Config\BotConfig;
use HybridGram\Telegram\Sender\OutgoingDispatcherInterface;
use HybridGram\Telegram\TelegramBotApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Phptg\BotApi\FailResult;

final class GetWebhookInfoCommand extends Command
{
    protected $signature = 'hybridgram:webhook:info {--bot=main}';

    protected $description = 'Get webhook info for Telegram bot';

    public function handle(): void
    {
        $botId = $this->option('bot');
        $config = BotConfig::getBotConfig($botId);
        
        if ($config === null) {
            $this->error("Bot config not found for bot: {$botId}");
            return;
        }

        $dispatcher = App::make(OutgoingDispatcherInterface::class);
        $apiWrapper = (new TelegramBotApi($config->token, 'https://api.telegram.org', null, $dispatcher))->withBotId($botId);

        $info = $apiWrapper->getWebhookInfo();

        if ($info instanceof FailResult) {
            $this->error("Failed to get webhook info for bot {$botId}: {$info->response->body}");
        } else {
            $this->info("Webhook info for bot {$botId}:");
            $this->line(json_encode($info, JSON_PRETTY_PRINT));
        }
    }
}
