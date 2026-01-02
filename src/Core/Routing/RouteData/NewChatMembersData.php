<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

/**
 * @param User[] $newChatMembers
 */
final readonly class NewChatMembersData extends AbstractRouteData
{
    /**
     * @param User[] $newChatMembers
     */
    public function __construct(
        Update $update,
        public array $newChatMembers,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }
}


