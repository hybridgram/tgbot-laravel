<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Sticker\Sticker;
use Phptg\BotApi\Type\Update\Update;

final readonly class StickerData extends AbstractRouteData
{
    public function __construct(
        Update       $update,
        public Sticker $sticker,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

