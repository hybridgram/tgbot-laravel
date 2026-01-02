<?php

declare(strict_types=1);

namespace HybridGram\Console;

use Illuminate\Console\Command;
use HybridGram\Core\Telegram\TelegramApiWrapper;

final class DeleteWebhookCommand extends Command
{
    protected $signature = 'hybridgram:webhook:delete {--bot=main}';

    protected $description = 'Delete webhook for Telegram bot';

    public function handle(TelegramApiWrapper $apiWrapper): void
    {
        $botId = $this->option('bot');

        $result = $apiWrapper->deleteWebhook($botId);

        if ($result) {
            $this->info("Webhook deleted successfully for bot: {$botId}");
        } else {
            $this->error("Failed to delete webhook for bot: {$botId}");
        }
    }
}
