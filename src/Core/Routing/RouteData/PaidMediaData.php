<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\PaidMediaInfo;
use Phptg\BotApi\Type\Update\Update;

final readonly class PaidMediaData extends AbstractRouteData
{
    public function __construct(
        Update         $update,
        public PaidMediaInfo $paidMedia,
        string         $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

