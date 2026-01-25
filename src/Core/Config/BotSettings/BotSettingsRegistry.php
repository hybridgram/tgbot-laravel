<?php

declare(strict_types=1);

namespace HybridGram\Core\Config\BotSettings;

use Closure;

/**
 * Registry for storing and retrieving BotSettings for each bot.
 *
 * Usage in a service provider:
 * ```php
 * use HybridGram\Core\Config\BotSettings\BotSettings;
 * use HybridGram\Core\Config\BotSettings\BotSettingsRegistry;
 *
 * public function boot(): void
 * {
 *     BotSettingsRegistry::forBot('main', function () {
 *         return BotSettings::create()
 *             ->description('My awesome bot')
 *             ->description('Мой крутой бот', 'ru')
 *             ->shortDescription('Best bot ever')
 *             ->shortDescription('Лучший бот', 'ru');
 *     });
 * }
 * ```
 */
final class BotSettingsRegistry
{
    /**
     * @var array<string, Closure(): BotSettings>
     */
    private static array $settings = [];

    /**
     * Register BotSettings for a specific bot.
     *
     * @param string $botId The bot identifier
     * @param Closure(): BotSettings $callback Callback that returns BotSettings instance
     */
    public static function forBot(string $botId, Closure $callback): void
    {
        self::$settings[$botId] = $callback;
    }

    /**
     * Get BotSettings for a specific bot.
     *
     * @param string $botId The bot identifier
     * @return BotSettings|null Returns null if no settings registered for this bot
     */
    public static function get(string $botId): ?BotSettings
    {
        if (! isset(self::$settings[$botId])) {
            return null;
        }

        return (self::$settings[$botId])();
    }

    /**
     * Check if settings are registered for a bot.
     */
    public static function has(string $botId): bool
    {
        return isset(self::$settings[$botId]);
    }

    /**
     * Get all registered bot IDs.
     *
     * @return string[]
     */
    public static function registeredBots(): array
    {
        return array_keys(self::$settings);
    }

    /**
     * Clear all registered settings.
     * Useful for testing.
     */
    public static function clear(): void
    {
        self::$settings = [];
    }

    /**
     * Clear settings for a specific bot.
     */
    public static function clearBot(string $botId): void
    {
        unset(self::$settings[$botId]);
    }
}
