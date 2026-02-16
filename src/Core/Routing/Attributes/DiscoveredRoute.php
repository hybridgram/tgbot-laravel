<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

final readonly class DiscoveredRoute
{
    /**
     * @param  class-string  $className
     * @param  list<TelegramRouteConfigAttribute>  $classConfigAttributes
     * @param  list<TelegramRouteConfigAttribute>  $methodConfigAttributes
     */
    public function __construct(
        public string $className,
        public string $methodName,
        public TelegramRouteAttribute $routeAttribute,
        public array $classConfigAttributes,
        public array $methodConfigAttributes,
    ) {}
}
