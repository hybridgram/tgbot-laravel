<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
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

    public static function match(Update $update, TelegramRoute $route): ?DiceData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->dice)) {
            return null;
        }

        return new DiceData($update, $update->message->dice, $route->botId);
    }
}
