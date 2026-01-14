<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}
