<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Update\Update;

/**
 * @property array<array<PhotoSize>> $photoSizes Массив массивов PhotoSize для всех фото в медиа-группе
 */
final readonly class PhotoMediaGroupData extends AbstractRouteData
{
    /**
     * @param  array<array<PhotoSize>>  $photoSizes  Массив массивов PhotoSize для всех фото в медиа-группе
     */
    public function __construct(
        Update $update,
        public array $photoSizes,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
