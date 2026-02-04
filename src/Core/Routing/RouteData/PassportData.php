<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Passport\PassportData as TelegramPassportData;
use Phptg\BotApi\Type\Update\Update;

final readonly class PassportData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public TelegramPassportData $passportData,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?PassportData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->passportData)) {
            return null;
        }

        return new PassportData($update, $update->message->passportData, $route->botId);
    }
}
