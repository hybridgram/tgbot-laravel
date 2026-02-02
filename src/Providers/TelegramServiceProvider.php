<?php

declare(strict_types=1);

namespace HybridGram\Providers;

use HybridGram\Auth\TelegramGuard;
use HybridGram\Auth\TelegramUserProvider;
use HybridGram\Console\ClearOptimizationsCommand;
use HybridGram\Console\DeleteWebhookCommand;
use HybridGram\Console\GetWebhookInfoCommand;
use HybridGram\Console\OptimizeRoutesCommand;
use HybridGram\Console\SetBotSettingsCommand;
use HybridGram\Console\SetWebhookCommand;
use HybridGram\Console\StartPollingCommand;
use HybridGram\Console\TelegramRouteListCommand;
use HybridGram\Core\Config\BotConfig;
use HybridGram\Http\Middlewares\ForceJsonResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
                SetBotSettingsCommand::class,
            ]);

            $this->optimizes(
                optimize: 'hybridgram:optimize',
                clear: 'hybridgram:clear-optimizations',
            );
        }

        $this->provideResources();
        $this->loadRoutes();
    }

    public function register(): void
    {
        $this->app->register(TelegramBindingsServiceProvider::class);
        $this->registerAuthGuard();
    }

    private function registerAuthGuard(): void
    {
        $guards = Config::get('auth.guards', []);
        if (! isset($guards['hybridgram'])) {
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

    private function provideResources(): void
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
        $this->publishes([
            __DIR__.'/../../stubs/ProcessTelegramUpdateJob.stub' => app_path('Jobs/ProcessTelegramUpdateJob.php'),
        ], 'hybridgram-jobs');
        // Also publish job stub with config for convenience
        $this->publishes([
            __DIR__.'/../../config/config.php' => config_path('hybridgram.php'),
            __DIR__.'/../../stubs/ProcessTelegramUpdateJob.stub' => app_path('Jobs/ProcessTelegramUpdateJob.php'),
        ], 'hybridgram-config');
    }

    private function loadRoutes(): void
    {
        $routesPath = file_exists(base_path('routes/telegram-webhook.php'))
            ? base_path('routes/telegram-webhook.php')
            : __DIR__.'/../../routes/telegram-webhook.php';

        Route::bind('botId', function (string $value) {
            return BotConfig::getBotConfig($value);
        });

        Route::group(['middleware' => [SubstituteBindings::class, ForceJsonResponse::class]], function () use ($routesPath) {
            $this->loadRoutesFrom($routesPath);
        });
    }
}
