<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}
