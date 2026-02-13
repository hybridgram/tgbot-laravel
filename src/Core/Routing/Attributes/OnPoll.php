<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\TelegramRouteBuilder;
use HybridGram\Telegram\Poll\PollType;

#[Attribute(Attribute::TARGET_METHOD)]
final class OnPoll implements TelegramRouteAttribute
{
    public function __construct(
        public ?bool $isAnonymous = null,
        public ?PollType $pollType = null,
    ) {}

    /** @param \Closure|string|array<int, string>  $action */
    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $pollOptions = ($this->isAnonymous !== null || $this->pollType !== null)
            ? new PollOptions($this->isAnonymous, $this->pollType)
            : null;

        $builder->onPoll($action, $pollOptions);
    }
}
