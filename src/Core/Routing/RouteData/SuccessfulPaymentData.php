<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
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

    public static function match(Update $update, TelegramRoute $route): ?SuccessfulPaymentData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->successfulPayment)) {
            return null;
        }

        return new SuccessfulPaymentData($update, $update->message->successfulPayment, $route->botId);
    }
}
