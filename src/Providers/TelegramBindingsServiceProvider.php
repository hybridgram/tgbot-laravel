<?php

declare(strict_types=1);

namespace HybridGram\Providers;

use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\HybridGramBotManager;
use HybridGram\Core\Middleware\MiddlewareManager;
use HybridGram\Core\Routing\TelegramRouter;
use HybridGram\Core\State\StateManager;
use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Telegram\RateLimiter\CacheOutgoingRateLimiter;
use HybridGram\Telegram\RateLimiter\OutgoingRateLimiterInterface;
use HybridGram\Telegram\Sender\DirectOutgoingDispatcher;
use HybridGram\Telegram\Sender\OutgoingDispatcherInterface;
use HybridGram\Telegram\Sender\QueueOutgoingDispatcher;
use HybridGram\Telegram\TelegramBotApi;
use Illuminate\Support\ServiceProvider;

final class TelegramBindingsServiceProvider extends ServiceProvider
{
    protected bool $defer = true;

    public function register(): void
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

        $this->app->singleton(OutgoingRateLimiterInterface::class, function ($app) {
            $sendingConfig = config('hybridgram.sending', []);
            $rateLimit = (int) ($sendingConfig['rate_limit_per_minute'] ?? 1800);
            $reserveHigh = (int) ($sendingConfig['reserve_high_per_minute'] ?? 300);

            return new CacheOutgoingRateLimiter($rateLimit, $reserveHigh);
        });

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

            return new DirectOutgoingDispatcher;
        });

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

    public function provides(): array
    {
        return [
            HybridGramBotManager::class,
            'hybridgram',
            TelegramRouter::class,
            MiddlewareManager::class,
            StateManagerInterface::class,
            OutgoingRateLimiterInterface::class,
            OutgoingDispatcherInterface::class,
            TelegramBotApi::class,
        ];
    }
}
