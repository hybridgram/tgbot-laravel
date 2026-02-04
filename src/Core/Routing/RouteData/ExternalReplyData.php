<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\ExternalReplyInfo;
use Phptg\BotApi\Type\Update\Update;

final readonly class ExternalReplyData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ExternalReplyInfo $externalReply,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ExternalReplyData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->externalReply === null) {
            return null;
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new ExternalReplyData($update, $update->message->externalReply, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            return new ExternalReplyData($update, $update->message->externalReply, $route->botId);
        }

        return null;
    }
}
