<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\MessageAutoDeleteTimerChanged;
use Phptg\BotApi\Type\Update\Update;

final readonly class AutoDeleteTimerChangedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public MessageAutoDeleteTimerChanged $messageAutoDeleteTimerChanged,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?AutoDeleteTimerChangedData
    {
        if ($update->message === null) {
            return null;
        }

        if ($update->message->messageAutoDeleteTimerChanged === null) {
            return null;
        }

        return new AutoDeleteTimerChangedData($update, $update->message->messageAutoDeleteTimerChanged, $route->botId);
    }
}
