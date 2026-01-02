<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use Phptg\BotApi\Type\Update\Update;

class RateLimitTelegramRouteMiddleware implements TelegramRouteMiddlewareInterface
{
    private array $userRequests = [];
    private int $maxRequests;
    private int $timeWindow;
    
    public function __construct(int $maxRequests = 10, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function handle(Update $update, callable $next): mixed
    {
        $userId = $this->getUserId($update);
        
        if (!$userId) {
            return $next($update);
        }
        
        $now = time();
        $userKey = (string) $userId;

        if (isset($this->userRequests[$userKey])) {
            $this->userRequests[$userKey] = array_filter(
                $this->userRequests[$userKey],
                fn($timestamp) => $now - $timestamp < $this->timeWindow
            );
        } else {
            $this->userRequests[$userKey] = [];
        }

        if (count($this->userRequests[$userKey]) >= $this->maxRequests) {
            logger()->warning('Rate limit exceeded', [
                'user_id' => $userId,
                'requests_count' => count($this->userRequests[$userKey])
            ]);

            return null;
        }

        $this->userRequests[$userKey][] = $now;
        
        return $next($update);
    }
    
    private function getUserId(Update $update): ?int
    {
        if ($update->message?->from?->id) {
            return $update->message->from->id;
        }
        
        if ($update->callbackQuery?->from?->id) {
            return $update->callbackQuery->from->id;
        }
        
        if ($update->inlineQuery?->from?->id) {
            return $update->inlineQuery->from->id;
        }
        
        if ($update->chosenInlineResult?->from?->id) {
            return $update->chosenInlineResult->from->id;
        }
        
        if ($update->shippingQuery?->from?->id) {
            return $update->shippingQuery->from->id;
        }
        
        if ($update->preCheckoutQuery?->from?->id) {
            return $update->preCheckoutQuery->from->id;
        }
        
        if ($update->pollAnswer?->user?->id) {
            return $update->pollAnswer->user->id;
        }
        
        return null;
    }
}
