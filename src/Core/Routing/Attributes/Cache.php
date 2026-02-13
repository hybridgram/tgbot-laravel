<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Cache implements TelegramRouteConfigAttribute
{
    public function __construct(
        public int $ttl,
        public ?string $key = null,
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->cache($this->ttl, $this->key);
    }
}
