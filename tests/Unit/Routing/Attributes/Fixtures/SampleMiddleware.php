<?php

declare(strict_types=1);

namespace Tests\Unit\Routing\Attributes\Fixtures;

use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use Phptg\BotApi\Type\Update\Update;

class SampleMiddleware implements TelegramRouteMiddlewareInterface
{
    public function handle(Update $update, \Closure $next): mixed
    {
        return $next($update);
    }
}
