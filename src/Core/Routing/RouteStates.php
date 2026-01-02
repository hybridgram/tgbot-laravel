<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use HybridGram\Core\State\State;

/**
 * DTO for current chat and user states used in route matching.
 */
final readonly class RouteStates
{
    public function __construct(
        public ?State $chatState = null,
        public ?State $userState = null,
    ) {}

    /**
     * Create RouteStates with both states as null
     */
    public static function empty(): self
    {
        return new self();
    }
}

