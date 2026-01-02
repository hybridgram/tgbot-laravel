<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Payment\Invoice;
use Phptg\BotApi\Type\Update\Update;

final readonly class InvoiceData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Invoice $invoice,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

