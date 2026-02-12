<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions;

use HybridGram\Telegram\ChatMember\ChatMemberStatus;

/**
 * Options for filtering ChatMember updates
 */
final readonly class ChatMemberOptions
{
    /**
     * @param  bool|null  $isBot  Filter by isBot for user (from). null - any value
     * @param  ChatMemberStatus[]|null  $allowedStatuses  Allowed statuses for newChatMember. null - any statuses
     */
    public function __construct(
        public ?bool $isBot = null,
        public ?array $allowedStatuses = null,
    ) {
        foreach ($allowedStatuses as $status) {
            if (! ($status instanceof ChatMemberStatus)) {
                throw new \InvalidArgumentException('All allowedStatuses must be instances of ChatMemberStatus');
            }
        }
    }
}
