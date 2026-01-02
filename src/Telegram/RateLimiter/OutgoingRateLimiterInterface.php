<?php

declare(strict_types=1);

namespace HybridGram\Telegram\RateLimiter;

use HybridGram\Telegram\Priority;

interface OutgoingRateLimiterInterface
{
    /**
     * Atomically check and reserve a slot for sending.
     *
     * In queue mode this should be used by workers so that multiple workers do not exceed the limit.
     *
     * @param string $botId Bot identifier
     * @param Priority $priority Request priority
     * @return RateLimitDecision If allowed, the slot is reserved immediately.
     */
    public function acquire(string $botId, Priority $priority): RateLimitDecision;

    /**
     * Check if a request can be sent now, or if it needs to be delayed.
     *
     * @param string $botId Bot identifier
     * @param Priority $priority Request priority
     * @return RateLimitDecision
     */
    public function check(string $botId, Priority $priority): RateLimitDecision;

    /**
     * Record that a request was sent (increment counter).
     *
     * @param string $botId Bot identifier
     */
    public function record(string $botId): void;

    /**
     * Get current usage for a bot (requests sent in current minute).
     *
     * @param string $botId Bot identifier
     * @return int Number of requests sent
     */
    public function currentUsage(string $botId): int;

    /**
     * Get remaining capacity for a bot at given priority.
     *
     * @param string $botId Bot identifier
     * @param Priority $priority Request priority
     * @return int Remaining requests available
     */
    public function remainingCapacity(string $botId, Priority $priority): int;
}

