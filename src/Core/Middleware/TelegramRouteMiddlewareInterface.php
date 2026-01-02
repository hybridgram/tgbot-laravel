<?php

declare(strict_types=1);

namespace HybridGram\Core\Middleware;

use Phptg\BotApi\Type\Update\Update;

interface TelegramRouteMiddlewareInterface
{
    public function handle(Update $update, callable $next): mixed;
}
