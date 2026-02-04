<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Update\Update;

final readonly class PhotoData extends AbstractRouteData
{
    /**
     * @param  array<PhotoSize>  $photoSizes
     */
    public function __construct(
        Update $update,
        public array $photoSizes,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PhotoData
    {
        if (empty($update->message->photo)) {
            return null;
        }

        if ($update->message->mediaGroupId !== null) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new PhotoData($update, $update->message->photo, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new PhotoData($update, $update->message->photo, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new PhotoData($update, $update->message->photo, $route->botId);
        }

        return null;
    }
}
