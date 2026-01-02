<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

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
}

