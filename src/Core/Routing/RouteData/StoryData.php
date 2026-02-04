<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Story;
use Phptg\BotApi\Type\Update\Update;

final readonly class StoryData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Story $story,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?StoryData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->story)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new StoryData($update, $update->message->story, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new StoryData($update, $update->message->story, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new StoryData($update, $update->message->story, $route->botId);
        }

        return null;
    }
}
