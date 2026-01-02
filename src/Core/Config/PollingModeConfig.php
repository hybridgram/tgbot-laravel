<?php

declare(strict_types=1);

namespace HybridGram\Core\Config;
/**
 * @param array<string> $allowedUpdates
 */
final class PollingModeConfig
{
    public function __construct(
        public int $limit = 100,
        public array $allowedUpdates = [],
        public int $timeout = 5,
    ) {}
}
