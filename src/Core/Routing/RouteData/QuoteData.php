<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\TextQuote;
use Phptg\BotApi\Type\Update\Update;

final readonly class QuoteData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public TextQuote $quote,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


