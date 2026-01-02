<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Audio;
use Phptg\BotApi\Type\Update\Update;

final readonly class AudioData extends AbstractRouteData
{
    public function __construct(
        Update       $update,
        public Audio $audio,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

