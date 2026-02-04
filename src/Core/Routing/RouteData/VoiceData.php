<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\Voice;

final readonly class VoiceData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Voice $voice,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?VoiceData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->voice)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new VoiceData($update, $update->message->voice, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new VoiceData($update, $update->message->voice, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new VoiceData($update, $update->message->voice, $route->botId);
        }

        return null;
    }
}
