<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\Inline\InlineQuery;
use Phptg\BotApi\Type\Update\Update;

final readonly class InlineQueryData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public InlineQuery $inlineQuery,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?InlineQueryData
    {
        if ($update->inlineQuery === null) {
            return null;
        }

        $query = $update->inlineQuery->query ?? '';

        if ($route->pattern instanceof \Closure) {
            return new InlineQueryData($update, $update->inlineQuery, $route->botId);
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new InlineQueryData($update, $update->inlineQuery, $route->botId);
        }

        if (is_string($route->pattern) && Str::is($route->pattern, $query)) {
            return new InlineQueryData($update, $update->inlineQuery, $route->botId);
        }

        return null;
    }
}
