<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Dice;
use Phptg\BotApi\Type\Update\Update;

final readonly class DiceData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Dice $dice,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

