<?php

declare(strict_types=1);

namespace HybridGram\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use HybridGram\Core\Routing\TelegramRouter;

final class ClearOptimizationsCommand extends Command
{
    protected $signature = 'hybridgram:clear-optimizations';
    protected $description = 'Clear cached Telegram routes';

    public function handle(): int
    {
        /** @var TelegramRouter $router */
        $router = App::get(TelegramRouter::class);
        
        $router->clearRoutesCache();
        
        $this->info('Telegram routes cache has been cleared successfully!');
        
        return self::SUCCESS;
    }
}
