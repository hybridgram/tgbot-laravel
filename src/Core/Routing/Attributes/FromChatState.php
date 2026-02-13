<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class FromChatState implements TelegramRouteConfigAttribute
{
    /**
     * @param  array<string|\BackedEnum>  $states
     */
    public function __construct(
        public array $states,
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->fromChatState($this->states);
    }
}
