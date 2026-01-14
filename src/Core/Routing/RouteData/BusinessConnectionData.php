<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\BusinessConnection;
use Phptg\BotApi\Type\Update\Update;

final readonly class BusinessConnectionData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public BusinessConnection $businessConnection,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
