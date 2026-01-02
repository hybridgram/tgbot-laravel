<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

final readonly class LeftChatMemberData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public User $leftChatMember,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


