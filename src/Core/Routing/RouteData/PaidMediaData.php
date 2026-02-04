<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\PaidMediaInfo;
use Phptg\BotApi\Type\Update\Update;

final readonly class PaidMediaData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public PaidMediaInfo $paidMedia,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PaidMediaData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->paidMedia)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new PaidMediaData($update, $update->message->paidMedia, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new PaidMediaData($update, $update->message->paidMedia, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new PaidMediaData($update, $update->message->paidMedia, $route->botId);
        }

        return null;
    }
}
