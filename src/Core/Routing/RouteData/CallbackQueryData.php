<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Update\Update;

final readonly class CallbackQueryData extends AbstractRouteData
{
    public function __construct(
        Update               $update,
        public string        $action,
        public array         $params = [],
        public CallbackQuery $query,
        string               $botId,
    )
    {
        parent::__construct($update, $botId);
    }
}

