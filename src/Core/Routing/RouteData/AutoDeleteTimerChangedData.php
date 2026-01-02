<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\MessageAutoDeleteTimerChanged;
use Phptg\BotApi\Type\Update\Update;

final readonly class AutoDeleteTimerChangedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public MessageAutoDeleteTimerChanged $messageAutoDeleteTimerChanged,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


