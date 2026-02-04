<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\Checklist;
use Phptg\BotApi\Type\Update\Update;

final readonly class ChecklistData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Checklist $checklist,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?ChecklistData
    {
        if ($update->message === null) {
            return null;
        }

        if (empty($update->message->checklist)) {
            return null;
        }

        return new ChecklistData($update, $update->message->checklist, $route->botId);
    }
}
