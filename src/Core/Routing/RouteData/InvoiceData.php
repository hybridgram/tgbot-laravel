<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
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

    public static function match(Update $update, TelegramRoute $route): ?InvoiceData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->invoice)) {
            return null;
        }

        return new InvoiceData($update, $update->message->invoice, $route->botId);
    }
}
