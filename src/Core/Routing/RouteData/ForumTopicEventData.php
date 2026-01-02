<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\ForumTopicClosed;
use Phptg\BotApi\Type\ForumTopicCreated;
use Phptg\BotApi\Type\ForumTopicEdited;
use Phptg\BotApi\Type\ForumTopicReopened;
use Phptg\BotApi\Type\Update\Update;

final readonly class ForumTopicEventData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        /** One of: forum_topic_created, forum_topic_edited, forum_topic_closed, forum_topic_reopened */
        public string $event,
        public ForumTopicCreated|ForumTopicEdited|ForumTopicClosed|ForumTopicReopened $payload,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


