<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions;

use HybridGram\Telegram\ChatMember\ChatMemberStatus;

/**
 * Опции для фильтрации ChatMember обновлений
 */
readonly class ChatMemberOptions
{
    /**
     * @param bool|null $isBot Фильтр по isBot для пользователя (from). null - любое значение
     * @param ChatMemberStatus[]|null $allowedStatuses Разрешенные статусы для newChatMember. null - любые статусы
     */
    public function __construct(
        public ?bool $isBot = null,
        public ?array $allowedStatuses = null,
    ) {
        foreach ($allowedStatuses as $status) {
            if (!($status instanceof ChatMemberStatus)) {
                throw new \InvalidArgumentException('All allowedStatuses must be instances of ChatMemberStatus');
            }
        }
    }
}
