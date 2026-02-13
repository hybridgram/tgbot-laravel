<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use Attribute;
use HybridGram\Core\Routing\TelegramRouteBuilder;

#[Attribute(Attribute::TARGET_METHOD)]
final class OnDocument implements TelegramRouteAttribute
{
    /**
     * @param  array<\HybridGram\Telegram\Document\MimeType|string>|null  $documentOptions
     */
    public function __construct(
        public ?string $pattern = null,
        public ?array $documentOptions = null,
    ) {}

    /** @param \Closure|string|array<int, string>  $action */
    public function registerRoute(TelegramRouteBuilder $builder, \Closure|string|array $action): void
    {
        $builder->onDocument($action, $this->pattern, $this->documentOptions);
    }
}
