<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class ChatTypes implements TelegramRouteConfigAttribute
{
    /**
     * @param  ChatType[]|null  $chatTypes  null means all chat types
     */
    public function __construct(
        public ?array $chatTypes,
    ) {}

    public function applyToBuilder(TelegramRouteBuilder $builder): void
    {
        $builder->chatTypes($this->chatTypes);
    }
}
