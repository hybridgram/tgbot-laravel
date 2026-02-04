<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\ForumTopicCreated;
use Phptg\BotApi\Type\Update\Update;

final readonly class ForumTopicCreatedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ForumTopicCreated $forumTopicCreated,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ForumTopicCreatedData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->forumTopicCreated instanceof ForumTopicCreated) {
            return new ForumTopicCreatedData($update, $update->message->forumTopicCreated, $route->botId);
        }

        return null;
    }
}
