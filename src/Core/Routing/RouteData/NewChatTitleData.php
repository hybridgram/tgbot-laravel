<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Update\Update;

final readonly class NewChatTitleData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public string $newChatTitle,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?NewChatTitleData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->newChatTitle)) {
            return null;
        }

        return new NewChatTitleData($update, $update->message->newChatTitle, $route->botId);
    }
}
