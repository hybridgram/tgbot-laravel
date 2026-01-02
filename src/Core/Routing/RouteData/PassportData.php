<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}

