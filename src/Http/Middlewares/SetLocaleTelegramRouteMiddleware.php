<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use HybridGram\Core\UpdateHelper;
use Illuminate\Support\Facades\App;
use Phptg\BotApi\Type\Update\Update;

final class SetLocaleTelegramRouteMiddleware implements TelegramRouteMiddlewareInterface
{
    /**
     * @param array<string>|null $supportedLocales List of supported locales. If null, any locale from Telegram will be used.
     * @param string|null $fallbackLocale Fallback locale if user's locale is not supported or not available.
     */
    public function __construct(
        private ?array $supportedLocales = null,
        private ?string $fallbackLocale = null,
    ) {}

    public function handle(Update $update, callable $next): mixed
    {
        $user = UpdateHelper::getUserFromUpdate($update);
        $locale = $user?->languageCode;

        if ($locale !== null) {
            $locale = $this->normalizeLocale($locale);
            
            if ($this->isLocaleSupported($locale)) {
                App::setLocale($locale);
            } elseif ($this->fallbackLocale !== null) {
                App::setLocale($this->fallbackLocale);
            }
        } elseif ($this->fallbackLocale !== null) {
            App::setLocale($this->fallbackLocale);
        }

        return $next($update);
    }

    private function normalizeLocale(string $locale): string
    {
        // Telegram sends language codes like "en", "ru", "uk", "pt-br"
        // Laravel typically uses "en", "ru", "uk", "pt_BR"
        // We normalize hyphen to underscore for consistency
        return str_replace('-', '_', $locale);
    }

    private function isLocaleSupported(string $locale): bool
    {
        if ($this->supportedLocales === null) {
            return true;
        }

        // Check exact match
        if (in_array($locale, $this->supportedLocales, true)) {
            return true;
        }

        // Check base language match (e.g., "en" matches "en_US")
        $baseLocale = explode('_', $locale)[0];
        
        return in_array($baseLocale, $this->supportedLocales, true);
    }
}
