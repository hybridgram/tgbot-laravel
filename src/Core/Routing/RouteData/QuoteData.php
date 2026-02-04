<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\TextQuote;
use Phptg\BotApi\Type\Update\Update;

final readonly class QuoteData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public TextQuote $quote,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?QuoteData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->quote === null) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new QuoteData($update, $update->message->quote, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            return new QuoteData($update, $update->message->quote, $route->botId);
        }

        if (is_string($route->pattern) && Str::is($route->pattern, $update->message->quote->text)) {
            return new QuoteData($update, $update->message->quote, $route->botId);
        }

        return null;
    }
}
