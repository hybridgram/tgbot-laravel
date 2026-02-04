<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;

final readonly class ReplyData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Message $replyToMessage,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ReplyData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->replyToMessage === null) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new ReplyData($update, $update->message->replyToMessage, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            return new ReplyData($update, $update->message->replyToMessage, $route->botId);
        }

        if (is_string($route->pattern) && $update->message->text !== null) {
            if (Str::is($route->pattern, $update->message->text)) {
                return new ReplyData($update, $update->message->replyToMessage, $route->botId);
            }
        }

        return null;
    }
}
