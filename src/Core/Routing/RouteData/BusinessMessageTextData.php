<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\Update\Update;

final readonly class BusinessMessageTextData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public string $text,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?BusinessMessageTextData
    {
        if ($update->businessMessage === null) {
            return null;
        }

        if (! isset($update->businessMessage->text)) {
            return null;
        }

        $text = $update->businessMessage->text;

        if ($route->pattern === null || $route->pattern === '*') {
            return new BusinessMessageTextData($update, $text, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new BusinessMessageTextData($update, $text, $route->botId);
        }

        if (Str::is($route->pattern, $text)) {
            return new BusinessMessageTextData($update, $text, $route->botId);
        }

        return null;
    }
}
