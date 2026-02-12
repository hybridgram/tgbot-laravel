<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\CallbackQueryDataString;
use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Update\Update;

final readonly class CallbackQueryData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public string $action,
        /** @var array<string, string> */
        public array $params,
        public CallbackQuery $query,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?CallbackQueryData
    {
        if ($update->callbackQuery === null) {
            return null;
        }

        $data = $update->callbackQuery->data ?? '';

        try {
            $parsed = CallbackQueryDataString::parse($data);
        } catch (\Throwable) {
            return null;
        }

        if ($route->pattern !== null && $route->pattern !== '*' && $route->pattern !== $parsed->action) {
            return null;
        }

        if ($route->callbackQueryOptions !== null) {
            foreach ($route->callbackQueryOptions as $item) {
                if ($item->matches($parsed->params)) {
                    return new CallbackQueryData($update, $parsed->action, $parsed->params, $update->callbackQuery, $route->botId);
                }
            }

            return null;
        }

        if ($parsed->params !== []) {
            return null;
        }

        return new CallbackQueryData($update, $parsed->action, $parsed->params, $update->callbackQuery, $route->botId);
    }
}
