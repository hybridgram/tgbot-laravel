<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Inline\InlineQuery;
use Phptg\BotApi\Type\Update\Update;

final readonly class InlineQueryData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public InlineQuery $inlineQuery,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

