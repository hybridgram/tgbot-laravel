<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\ForumTopicClosed;
use Phptg\BotApi\Type\Update\Update;

final readonly class ForumTopicClosedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ForumTopicClosed $forumTopicClosed,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ForumTopicClosedData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->forumTopicClosed instanceof ForumTopicClosed) {
            return new ForumTopicClosedData($update, $update->message->forumTopicClosed, $route->botId);
        }

        return null;
    }
}
