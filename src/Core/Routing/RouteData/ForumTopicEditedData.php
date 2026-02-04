<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\ForumTopicEdited;
use Phptg\BotApi\Type\Update\Update;

final readonly class ForumTopicEditedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ForumTopicEdited $forumTopicEdited,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ForumTopicEditedData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->forumTopicEdited instanceof ForumTopicEdited) {
            return new ForumTopicEditedData($update, $update->message->forumTopicEdited, $route->botId);
        }

        return null;
    }
}
