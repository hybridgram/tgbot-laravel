<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use Illuminate\Support\Facades\Auth;
use Phptg\BotApi\Type\Update\Update;

final class AuthTelegramRouteMiddleware implements TelegramRouteMiddlewareInterface
{
    public function handle(Update $update, callable $next): mixed
    {
        try {
            /** @var \HybridGram\Auth\TelegramGuard $guard */
            $guard = Auth::guard('hybridgram');
            $guard->setUpdate($update);
            $guard->user();
        } catch (\Throwable $e) {
            logger()->warning('Telegram Guard authentication failed', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $next($update);
    }
}
