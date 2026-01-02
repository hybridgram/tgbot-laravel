<?php

declare(strict_types=1);

namespace HybridGram\Telegram\RateLimiter;

final readonly class RateLimitDecision
{
    public function __construct(
        public bool $allowNow,
        public int $delayMilliseconds = 0,
    ) {}

    public static function allow(): self
    {
        return new self(true, 0);
    }

    public static function delayMs(int $milliseconds): self
    {
        return new self(false, max(0, $milliseconds));
    }
}

