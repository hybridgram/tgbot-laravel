<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Location;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\Venue;

final readonly class VenueData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Venue $venue,
        public Location $location,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?VenueData
    {
        if (empty($update->message?->venue)) {
            return null;
        }

        return new VenueData($update, $update->message->venue, $update->message->location, $route->botId);
    }
}
