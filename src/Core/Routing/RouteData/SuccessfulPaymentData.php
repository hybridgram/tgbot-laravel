<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Payment\SuccessfulPayment;
use Phptg\BotApi\Type\Update\Update;

final readonly class SuccessfulPaymentData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public SuccessfulPayment $successfulPayment,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}

