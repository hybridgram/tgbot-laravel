<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\PollAnswer;
use Phptg\BotApi\Type\Update\Update;

final readonly class PollAnswerData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public PollAnswer $pollAnswer,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PollAnswerData
    {
        if ($update->pollAnswer === null) {
            return null;
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update, $update->pollAnswer)) {
                return null;
            }
        }

        return new PollAnswerData($update, $update->pollAnswer, $route->botId);
    }
}
