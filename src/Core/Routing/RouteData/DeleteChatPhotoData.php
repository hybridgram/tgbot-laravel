<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Update\Update;

final readonly class DeleteChatPhotoData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public bool $deleteChatPhoto,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?DeleteChatPhotoData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->deleteChatPhoto)) {
            return null;
        }

        return new DeleteChatPhotoData($update, (bool) $update->message->deleteChatPhoto, $route->botId);
    }
}
