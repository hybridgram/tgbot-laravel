<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\ChatBoostAdded;
use Phptg\BotApi\Type\Update\Update;

final readonly class BoostAddedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ChatBoostAdded $boostAdded,
        public ?int $senderBoostCount,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


