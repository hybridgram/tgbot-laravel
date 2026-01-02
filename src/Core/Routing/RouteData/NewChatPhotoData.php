<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Update\Update;

/**
 * @param PhotoSize[] $newChatPhoto
 */
final readonly class NewChatPhotoData extends AbstractRouteData
{
    /**
     * @param PhotoSize[] $newChatPhoto
     */
    public function __construct(
        Update $update,
        public array $newChatPhoto,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


