<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\MediaGroup\MediaGroupGrouper;
use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Update\Update;

/**
 * @property array<array<PhotoSize>> $photoSizes Array of PhotoSize arrays for all photos in the media group
 */
final readonly class PhotoMediaGroupData extends AbstractRouteData
{
    /**
     * @param  array<array<PhotoSize>>  $photoSizes  Array of PhotoSize arrays for all photos in the media group
     */
    public function __construct(
        Update $update,
        public array $photoSizes,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PhotoMediaGroupData
    {
        if (empty($update->message->photo)) {
            return null;
        }

        if ($update->message->mediaGroupId === null) {
            return null;
        }

        $mediaGroupId = $update->message->mediaGroupId;

        if ($route->pattern === null || $route->pattern === '*') {
            $allPhotos = MediaGroupGrouper::getGroupedPhotos($mediaGroupId);

            if (empty($allPhotos)) {
                return null;
            }

            return new PhotoMediaGroupData($update, $allPhotos, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            $allPhotos = MediaGroupGrouper::getGroupedPhotos($mediaGroupId);
            if (empty($allPhotos)) {
                return null;
            }

            return new PhotoMediaGroupData($update, $allPhotos, $route->botId);
        }

        $caption = $update->message->caption;

        if ($caption !== null && $caption === $route->pattern) {
            $allPhotos = MediaGroupGrouper::getGroupedPhotos($mediaGroupId);
            if (empty($allPhotos)) {
                return null;
            }

            return new PhotoMediaGroupData($update, $allPhotos, $route->botId);
        }

        return null;
    }
}
