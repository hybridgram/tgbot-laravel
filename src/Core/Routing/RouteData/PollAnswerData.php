<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\PollAnswer;
use Phptg\BotApi\Type\Update\Update;

final readonly class PollAnswerData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public PollAnswer $pollAnswer,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}
