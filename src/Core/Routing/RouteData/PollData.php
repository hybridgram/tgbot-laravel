<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Poll;
use Phptg\BotApi\Type\Update\Update;

final readonly class PollData extends AbstractRouteData
{
    public function __construct(
        public Poll $poll,
        Update $update,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
