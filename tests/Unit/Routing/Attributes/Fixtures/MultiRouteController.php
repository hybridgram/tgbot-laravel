<?php

declare(strict_types=1);

namespace Tests\Unit\Routing\Attributes\Fixtures;

use HybridGram\Core\Routing\Attributes\ForBot;
use HybridGram\Core\Routing\Attributes\OnAny;
use HybridGram\Core\Routing\Attributes\OnCommand;
use HybridGram\Core\Routing\Attributes\OnFallback;
use HybridGram\Core\Routing\Attributes\OnLocation;
use HybridGram\Core\Routing\Attributes\OnVenue;

#[ForBot('secondary')]
class MultiRouteController
{
    #[OnCommand(pattern: '/help')]
    public function help(): void {}

    #[OnVenue]
    public function venue(): void {}

    #[OnLocation]
    public function location(): void {}

    #[OnAny]
    public function any(): void {}

    #[OnFallback]
    public function fallback(): void {}
}
