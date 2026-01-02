<?php

namespace HybridGram\Core\Routing\RouteData;


use Phptg\BotApi\Type\PhotoSize;
use Phptg\BotApi\Type\Update\Update;

final readonly class PhotoData extends AbstractRouteData
{
    /**
     * @param array<PhotoSize> $photoSizes
     */
    public function __construct(
        Update       $update,
        public array $photoSizes,
        string       $botId,
    ) {
        parent::__construct($update, $botId);
    }
}