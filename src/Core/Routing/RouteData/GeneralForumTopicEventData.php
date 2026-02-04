<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\GeneralForumTopicHidden;
use Phptg\BotApi\Type\GeneralForumTopicUnhidden;
use Phptg\BotApi\Type\Update\Update;

final readonly class GeneralForumTopicEventData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        /** One of: general_forum_topic_hidden, general_forum_topic_unhidden */
        public string $event,
        public GeneralForumTopicHidden|GeneralForumTopicUnhidden $payload,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?GeneralForumTopicEventData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->generalForumTopicHidden instanceof GeneralForumTopicHidden) {
            return new GeneralForumTopicEventData(
                $update,
                'general_forum_topic_hidden',
                $update->message->generalForumTopicHidden,
                $route->botId,
            );
        }

        if ($update->message->generalForumTopicUnhidden instanceof GeneralForumTopicUnhidden) {
            return new GeneralForumTopicEventData(
                $update,
                'general_forum_topic_unhidden',
                $update->message->generalForumTopicUnhidden,
                $route->botId,
            );
        }

        return null;
    }
}
