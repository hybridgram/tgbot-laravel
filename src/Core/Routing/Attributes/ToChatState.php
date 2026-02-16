<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class ToChatState implements TelegramRouteConfigAttribute
{
    /**
     * @param  array<int|string, mixed>  $data
     */
    public function __construct(
        public string|\BackedEnum|null $state,
        public ?int $ttl = null,
        public array $data = [],
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->toChatState($this->state, $this->ttl, $this->data);
    }
}
