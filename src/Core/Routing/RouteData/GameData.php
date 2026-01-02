<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Game\Game;
use Phptg\BotApi\Type\Update\Update;

final readonly class GameData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Game $game,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

