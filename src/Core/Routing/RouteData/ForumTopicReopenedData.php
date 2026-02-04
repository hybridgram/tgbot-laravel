<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\ForumTopicReopened;
use Phptg\BotApi\Type\Update\Update;

final readonly class ForumTopicReopenedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ForumTopicReopened $forumTopicReopened,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ForumTopicReopenedData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->forumTopicReopened instanceof ForumTopicReopened) {
            return new ForumTopicReopenedData($update, $update->message->forumTopicReopened, $route->botId);
        }

        return null;
    }
}
