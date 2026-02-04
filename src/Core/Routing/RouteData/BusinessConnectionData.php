<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\BusinessConnection;
use Phptg\BotApi\Type\Update\Update;

final readonly class BusinessConnectionData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public BusinessConnection $businessConnection,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?BusinessConnectionData
    {
        if ($update->businessConnection === null) {
            return null;
        }

        return new BusinessConnectionData($update, $update->businessConnection, $route->botId);
    }
}
