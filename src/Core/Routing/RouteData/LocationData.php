<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Location;
use Phptg\BotApi\Type\Update\Update;

final readonly class LocationData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Location $location,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?LocationData
    {
        if (empty($update->message->venue)) {
            return null;
        }

        return new LocationData($update, $update->message->location, $route->botId);
    }
}
