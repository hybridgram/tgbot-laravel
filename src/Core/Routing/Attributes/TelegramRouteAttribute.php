<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use HybridGram\Core\Routing\TelegramRouteBuilder;

interface TelegramRouteAttribute
{
    /** @param \Closure|string|array<int, string>  $action */
    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void;
}
