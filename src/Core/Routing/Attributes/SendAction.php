<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\ActionType;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class SendAction implements TelegramRouteConfigAttribute
{
    public function __construct(
        public ActionType $action,
        public int $timeout = 5,
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->sendAction($this->action, $this->timeout);
    }
}
