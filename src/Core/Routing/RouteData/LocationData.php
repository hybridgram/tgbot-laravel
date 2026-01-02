<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Location;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\Venue;

final readonly class LocationData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Location $location,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
