<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use HybridGram\Core\Routing\TelegramRouteBuilder;

interface TelegramRouteConfigAttribute
{
    public function applyToBuilder(TelegramRouteBuilder $builder): void;
}
