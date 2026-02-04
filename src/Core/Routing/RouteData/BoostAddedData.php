<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
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

    public static function match(Update $update, TelegramRoute $route): ?BoostAddedData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->boostAdded === null) {
            return null;
        }

        return new BoostAddedData(
            $update,
            $update->message->boostAdded,
            $update->message->senderBoostCount,
            $route->botId,
        );
    }
}
