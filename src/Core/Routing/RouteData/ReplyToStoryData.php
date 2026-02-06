<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\Story;
use Phptg\BotApi\Type\Update\Update;

final readonly class ReplyToStoryData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Story $replyToStory,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ReplyToStoryData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->replyToStory === null) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new ReplyToStoryData($update, $update->message->replyToStory, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            return new ReplyToStoryData($update, $update->message->replyToStory, $route->botId);
        }

        if ($update->message->text !== null) {
            if (Str::is($route->pattern, $update->message->text)) {
                return new ReplyToStoryData($update, $update->message->replyToStory, $route->botId);
            }
        }

        return null;
    }
}
