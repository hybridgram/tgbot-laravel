<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Update\Update;

/**
 * @param  PhotoSize[]  $newChatPhoto
 */
final readonly class NewChatPhotoData extends AbstractRouteData
{
    /**
     * @param  PhotoSize[]  $newChatPhoto
     */
    public function __construct(
        Update $update,
        public array $newChatPhoto,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?NewChatPhotoData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->newChatPhoto)) {
            return null;
        }

        return new NewChatPhotoData($update, $update->message->newChatPhoto, $route->botId);
    }
}
