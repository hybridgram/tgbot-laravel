<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;

final readonly class BusinessMessageTextData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public string $text,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
