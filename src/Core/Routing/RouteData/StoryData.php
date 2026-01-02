<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Story;
use Phptg\BotApi\Type\Update\Update;

final readonly class StoryData extends AbstractRouteData
{
    public function __construct(
        Update       $update,
        public Story $story,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

