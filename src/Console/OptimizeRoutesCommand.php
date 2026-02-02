<?php

declare(strict_types=1);

namespace HybridGram\Console;

use HybridGram\Core\Routing\TelegramRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

final class OptimizeRoutesCommand extends Command
{
    protected $signature = 'hybridgram:optimize';

    protected $description = 'Cache Telegram routes for better performance';

    public function handle(): int
    {
        /** @var TelegramRouter $router */
        $router = App::get(TelegramRouter::class);

        $bots = config('bot.bots', []);
        foreach ($bots as $bot) {
            $router->registerRoutes($bot['routes_file']);
        }

        $router->cacheRoutes();

        $this->info('Telegram routes have been cached successfully!');

        return self::SUCCESS;
    }
}
