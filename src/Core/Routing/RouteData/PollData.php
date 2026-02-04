<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Poll;
use Phptg\BotApi\Type\Update\Update;

final readonly class PollData extends AbstractRouteData
{
    public function __construct(
        public Poll $poll,
        Update $update,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PollData
    {
        if ($update->message?->poll === null) {
            return null;
        }

        $options = $route->pollOptions;

        if ($options instanceof PollOptions) {
            if ($options->pollType?->value && $options->pollType->value !== $update->message->poll->type) {
                return null;
            }

            if ($options->isAnonymous && $options->isAnonymous !== $update->message->poll->isAnonymous) {
                return null;
            }
        }

        return new PollData($update->message->poll, $update, $route->botId);
    }
}
