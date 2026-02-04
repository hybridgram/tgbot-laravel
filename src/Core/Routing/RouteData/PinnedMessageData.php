<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\InaccessibleMessage;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;

final readonly class PinnedMessageData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Message|InaccessibleMessage $pinnedMessage,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PinnedMessageData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->pinnedMessage === null) {
            return null;
        }

        return new PinnedMessageData($update, $update->message->pinnedMessage, $route->botId);
    }
}
