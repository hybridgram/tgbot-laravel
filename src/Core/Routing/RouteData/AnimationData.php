<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Animation;
use Phptg\BotApi\Type\Update\Update;

final readonly class AnimationData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Animation $animation,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?AnimationData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->animation)) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new AnimationData($update, $update->message->animation, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new AnimationData($update, $update->message->animation, $route->botId);
        }

        if ($update->message->caption !== null && $update->message->caption === $route->pattern) {
            return new AnimationData($update, $update->message->animation, $route->botId);
        }

        return null;
    }
}
