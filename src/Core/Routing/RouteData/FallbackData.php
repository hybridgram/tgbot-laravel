<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;

final readonly class FallbackData extends AbstractRouteData
{
    public function __construct(
        Update           $update,
        string           $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

