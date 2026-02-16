<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\RouteOptions\ChatMemberOptions;
use HybridGram\Core\Routing\TelegramRouteBuilder;
use HybridGram\Telegram\ChatMember\ChatMemberStatus;

#[Attribute(Attribute::TARGET_METHOD)]
final class OnMyChatMember implements TelegramRouteAttribute
{
    /**
     * @param  array<ChatMemberStatus>|null  $allowedStatuses
     */
    public function __construct(
        public ?bool $isBot = null,
        public ?array $allowedStatuses = null,
    ) {}

    /** @param \Closure|string|array<int, string>  $action */
    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $chatMemberOptions = ($this->isBot !== null || $this->allowedStatuses !== null)
            ? new ChatMemberOptions($this->isBot, $this->allowedStatuses)
            : null;

        $builder->onMyChatMember($action, $chatMemberOptions);
    }
}
