<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class ForBot implements TelegramRouteConfigAttribute
{
    public function __construct(
        public string $botId,
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->forBot($this->botId);
    }
}
