<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class TgMiddlewares implements TelegramRouteConfigAttribute
{
    /**
     * @param  array<int, string|object>  $middlewares
     */
    public function __construct(
        public array $middlewares,
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->middlewares($this->middlewares);
    }
}
