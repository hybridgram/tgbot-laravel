<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Poll;
use Phptg\BotApi\Type\Update\Update;

final readonly class PollClosedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Poll $poll,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PollClosedData
    {
        if ($update->poll === null) {
            return null;
        }

        $options = $route->pollOptions;

        if ($options instanceof PollOptions) {
            if ($options->pollType?->value && $options->pollType->value !== $update->poll->type) {
                return null;
            }

            if ($options->isAnonymous && $options->isAnonymous !== $update->poll->isAnonymous) {
                return null;
            }
        }

        return new PollClosedData($update, $update->poll, $route->botId);
    }
}
