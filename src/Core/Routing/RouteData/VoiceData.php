<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\Voice;

final readonly class VoiceData extends AbstractRouteData
{
    public function __construct(
        Update       $update,
        public Voice $voice,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

