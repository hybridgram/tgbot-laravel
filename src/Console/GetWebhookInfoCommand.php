<?php

declare(strict_types=1);

namespace HybridGram\Console;

use Illuminate\Console\Command;
use HybridGram\Core\Telegram\TelegramApiWrapper;

final class GetWebhookInfoCommand extends Command
{
    protected $signature = 'hybridgram:webhook:info {--bot=main}';

    protected $description = 'Get webhook info for Telegram bot';

    public function handle(TelegramApiWrapper $apiWrapper): void
    {
        $botId = $this->option('bot');

        $info = $apiWrapper->getWebhookInfo($botId);

        if ($info) {
            $this->info("Webhook info for bot {$botId}:");
            $this->line(json_encode($info, JSON_PRETTY_PRINT));
        } else {
            $this->error("Failed to get webhook info for bot: {$botId}");
        }
    }
}
