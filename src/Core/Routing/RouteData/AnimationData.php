<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Animation;
use Phptg\BotApi\Type\Update\Update;

final readonly class AnimationData extends AbstractRouteData
{
    public function __construct(
        Update           $update,
        public Animation $animation,
        string           $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

