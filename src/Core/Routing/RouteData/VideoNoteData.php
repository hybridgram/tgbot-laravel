<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\VideoNote;

final readonly class VideoNoteData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public VideoNote $videoNote,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?VideoNoteData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->videoNote)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new VideoNoteData($update, $update->message->videoNote, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new VideoNoteData($update, $update->message->videoNote, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new VideoNoteData($update, $update->message->videoNote, $route->botId);
        }

        return null;
    }
}
