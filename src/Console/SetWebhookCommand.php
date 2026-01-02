<?php

declare(strict_types=1);

namespace HybridGram\Console;

use Illuminate\Console\Command;
use HybridGram\Core\Config\BotConfig;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\TelegramBotApi as VjikTelegramBotApi;

final class SetWebhookCommand extends Command
{
    protected $signature = 'hybridgram:webhook:set {--bot=main}';

    protected $description = 'Set webhook URL for Telegram bot';

    public function handle(): void
    {
        $config = BotConfig::getBotConfig($this->option('bot'));
        $telegram = new VjikTelegramBotApi($config->token);

        $url = route('telegram.bot.webhook', ['botId' => $this->option('bot')]);
        // todo secret token
        $result = $telegram->setWebhook($url);
        
        if ($result instanceof FailResult) {
            $this->error("error: {$result->response->body}");
        } else {
            $this->info('success');
        }
    }
}
