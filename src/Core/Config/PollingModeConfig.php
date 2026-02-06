<?php

declare(strict_types=1);

namespace HybridGram\Core\Config;

final class PollingModeConfig
{
    /**
     * @param  array<string>  $allowedUpdates
     */
    public function __construct(
        public int $limit = 100,
        public array $allowedUpdates = [],
        public int $timeout = 5,
    ) {}
}
