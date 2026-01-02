<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Poll;
use Phptg\BotApi\Type\Update\Update;

final readonly class PollClosedData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Poll $poll,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
