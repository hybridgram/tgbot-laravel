<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\ExternalReplyInfo;
use Phptg\BotApi\Type\Update\Update;

final readonly class ExternalReplyData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public ExternalReplyInfo $externalReply,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


