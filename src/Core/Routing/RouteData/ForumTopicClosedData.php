<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}
