<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Sticker\Sticker;
use Phptg\BotApi\Type\Update\Update;

final readonly class StickerData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Sticker $sticker,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?StickerData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->sticker)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new StickerData($update, $update->message->sticker, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new StickerData($update, $update->message->sticker, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new StickerData($update, $update->message->sticker, $route->botId);
        }

        return null;
    }
}
