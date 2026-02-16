<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_METHOD)]
final class OnTextMessage implements TelegramRouteAttribute
{
    public function __construct(
        public ?string $pattern = null,
    ) {}

    /** @param \Closure|string|array<int, string>  $action */
    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $builder->onTextMessage($action, $this->pattern);
    }
}
