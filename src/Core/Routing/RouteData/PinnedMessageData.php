<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\InaccessibleMessage;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;

final readonly class PinnedMessageData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Message|InaccessibleMessage $pinnedMessage,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


