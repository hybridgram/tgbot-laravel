<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Audio;
use Phptg\BotApi\Type\Update\Update;

final readonly class AudioData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Audio $audio,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?AudioData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->audio)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new AudioData($update, $update->message->audio, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new AudioData($update, $update->message->audio, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new AudioData($update, $update->message->audio, $route->botId);
        }

        return null;
    }
}
