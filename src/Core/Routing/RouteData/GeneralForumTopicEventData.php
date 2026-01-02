<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}


