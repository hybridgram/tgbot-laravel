<?php

declare(strict_types=1);

namespace HybridGram\Providers;

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use HybridGram\Auth\TelegramGuard;
use HybridGram\Auth\TelegramUserProvider;
use HybridGram\Console\DeleteWebhookCommand;
use HybridGram\Console\GetWebhookInfoCommand;
use HybridGram\Console\SetWebhookCommand;
use HybridGram\Console\OptimizeRoutesCommand;
use HybridGram\Console\ClearOptimizationsCommand;
use HybridGram\Console\StartPollingCommand;
use HybridGram\Console\TelegramRouteListCommand;
use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\Middleware\MiddlewareManager;
use HybridGram\Core\HybridGramBotManager;
use HybridGram\Core\Routing\TelegramRouter;
use HybridGram\Core\State\StateManager;
use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Http\Middlewares\ForceJsonResponse;
use HybridGram\Telegram\TelegramBotApi;
use HybridGram\Telegram\RateLimiter\OutgoingRateLimiterInterface;
use HybridGram\Telegram\RateLimiter\CacheOutgoingRateLimiter;
use HybridGram\Telegram\Sender\OutgoingDispatcherInterface;
use HybridGram\Telegram\Sender\SyncOutgoingDispatcher;
use HybridGram\Telegram\Sender\QueueOutgoingDispatcher;
use HybridGram\Telegram\Sender\DirectOutgoingDispatcher;
use Phptg\BotApi\Type\Update\Update;

final class TelegramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../'.('config/config.php'), 'hybridgram');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SetWebhookCommand::class,
                DeleteWebhookCommand::class,
                GetWebhookInfoCommand::class,
                OptimizeRoutesCommand::class,
                ClearOptimizationsCommand::class,
                StartPollingCommand::class,
                TelegramRouteListCommand::class,
            ]);

            $this->optimizes(
                optimize: 'hybridgram:optimize',
                clear: 'hybridgram:clear-optimizations',
            );
        }

        $this->provideResources();
        $this->loadRoutes();
    }

    public function provides(): array // todo возможно есть что-то лишнее
    {
        return [
            HybridGramBotManager::class,
            'hybridgram',
            TelegramRouter::class,
            MiddlewareManager::class,
            StateManagerInterface::class,
        ];
    }

    public function register(): void
    {
        $this->registerBindings();
        $this->registerAuthGuard();
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(
            HybridGramBotManager::class,
            fn ($app): HybridGramBotManager => new HybridGramBotManager
        );

        $this->app->alias(HybridGramBotManager::class, 'hybridgram');

        $this->app->singleton(TelegramRouter::class, function ($app) {
            return new TelegramRouter;
        });

        $this->app->singleton(MiddlewareManager::class, function ($app) {
            return new MiddlewareManager;
        });

        $this->app->singleton(StateManagerInterface::class, function ($app) {
            return new StateManager;
        });

        // Register Rate Limiter
        $this->app->singleton(OutgoingRateLimiterInterface::class, function ($app) {
            $sendingConfig = config('hybridgram.sending', []);
            $rateLimit = (int) ($sendingConfig['rate_limit_per_minute'] ?? 1800);
            $reserveHigh = (int) ($sendingConfig['reserve_high_per_minute'] ?? 300);
            return new CacheOutgoingRateLimiter($rateLimit, $reserveHigh);
        });

        // Register Outgoing Dispatcher (sync or queue based on config)
        $this->app->singleton(OutgoingDispatcherInterface::class, function ($app) {
            $sendingConfig = config('hybridgram.sending', []);
            $queueEnabled = (bool) ($sendingConfig['queue_enabled'] ?? false);

            if ($queueEnabled) {
                $queueNames = [
                    'high' => $sendingConfig['queues']['high'] ?? 'telegram-high',
                    'low' => $sendingConfig['queues']['low'] ?? 'telegram-low',
                ];
                return new QueueOutgoingDispatcher($queueNames);
            }

            // In non-queue (sync) mode we send directly without rate limiting.
            return new DirectOutgoingDispatcher();
        });

        // Register TelegramBotApi (our enhanced client with dispatcher)
        $this->app->bind(TelegramBotApi::class, function ($app, array $params) {
            $botId = $params['botId'] ?? null;

            if ($botId === null && $app->bound('telegram.botId')) {
                $botId = $app->make('telegram.botId');
            }

            if ($botId === null) {
                $botId = (string) (config('hybridgram.bots.0.bot_id') ?? '');
            }

            if ($botId === '') {
                throw new \RuntimeException('botId is required for TelegramBotApi.');
            }

            $config = BotConfig::getBotConfig($botId);
            if ($config === null) {
                throw new \RuntimeException("Bot config not found for botId '{$botId}'.");
            }

            $dispatcher = $app->make(OutgoingDispatcherInterface::class);
            return (new TelegramBotApi($config->token, 'https://api.telegram.org', null, $dispatcher))->withBotId($botId);
        });
    }

    protected function registerAuthGuard(): void
    {
        $guards = Config::get('auth.guards', []);
        if (!isset($guards['hybridgram'])) {
            Config::set('auth.guards.hybridgram', [
                'driver' => 'hybridgram',
                'provider' => null,
            ]);
        }

        Auth::extend('hybridgram', function ($app, $name, array $config) {
            $authConfig = config('hybridgram.auth', []);
            $userModel = $authConfig['user_model'] ?? 'App\\Models\\User';
            $telegramIdColumn = $authConfig['telegram_id_column'] ?? 'telegram_id';
            $autoCreateUser = (bool) ($authConfig['auto_create_user'] ?? false);

            $callback = null;
            if ($app->bound('hybridgram.user_creation_callback')) {
                $callback = $app->make('hybridgram.user_creation_callback');
            }

            $provider = new TelegramUserProvider(
                $userModel,
                $telegramIdColumn,
                $autoCreateUser,
                $callback
            );

            return new TelegramGuard($provider);
        });
    }

    protected function provideResources(): void
    {
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('hybridgram.php'),
        ], 'hybridgram-config');
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'hybridgram-migrations');
        $this->publishes([
            __DIR__.'/../../routes/telegram-webhook.php' => base_path('routes/telegram-webhook.php'),
        ], 'hybridgram-routes');
        $this->publishes([
            __DIR__.'/../../routes/telegram-webhook.php' => base_path('routes/telegram-webhook.php'),
            __DIR__.'/../../stubs/telegram.stub' => base_path('routes/telegram.php'),
        ], 'hybridgram-routes');
    }


    protected function loadRoutes(): void
    {
        $routesPath = file_exists(base_path('routes/telegram-webhook.php'))
            ? base_path('routes/telegram-webhook.php')
            : __DIR__.'/../../routes/telegram-webhook.php';

        Route::bind('botId', function ($value) {
            return BotConfig::getBotConfig($value);
        });

        Route::group(['middleware' => [SubstituteBindings::class, ForceJsonResponse::class]], function () use ($routesPath) {
            $this->loadRoutesFrom($routesPath);
        });
    }
}
