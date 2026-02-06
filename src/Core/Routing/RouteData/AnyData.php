<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Update\Update;

final readonly class AnyData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): AnyData
    {
        return new AnyData($update, $route->botId);
    }
}
