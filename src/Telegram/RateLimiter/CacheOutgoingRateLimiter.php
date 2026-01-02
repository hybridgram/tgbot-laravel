<?php

declare(strict_types=1);

namespace HybridGram\Telegram\RateLimiter;

use HybridGram\Telegram\Priority;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class CacheOutgoingRateLimiter implements OutgoingRateLimiterInterface
{
    private const int WINDOW_MILLISECONDS = 60_000;
    private const int CACHE_TTL_SECONDS = 180;
    private const string CACHE_KEY_PREFIX = 'tg:out:';

    public function __construct(
        private readonly int $rateLimitPerMinute,
        private readonly int $reserveHighPerMinute,
        /**
         * @var null|\Closure():int  Current time in milliseconds (for testing)
         */
        private readonly ?\Closure $nowMs = null,
    ) {}

    public function acquire(string $botId, Priority $priority): RateLimitDecision
    {
        $operation = function () use ($botId, $priority): RateLimitDecision {
            $effectiveLimit = $this->getEffectiveLimit($priority);
            $timestamps = $this->loadWindow($botId);
            $currentCount = count($timestamps);

            if ($currentCount < $effectiveLimit) {
                $timestamps[] = $this->nowMs();
                $this->saveWindow($botId, $timestamps);

                return RateLimitDecision::allow();
            }

            $nowMs = $this->nowMs();
            $index = max(0, $currentCount - $effectiveLimit);

            $targetTs = $timestamps[$index] ?? $timestamps[0] ?? $nowMs;
            $delayMs = ($targetTs + self::WINDOW_MILLISECONDS) - $nowMs;

            return RateLimitDecision::delayMs(max(0, $delayMs));
        };

        // Redis/memcached stores support atomic locks; fall back to best-effort without lock otherwise.
        $store = Cache::getStore();
        if ($store instanceof LockProvider) {
            try {
                /** @var RateLimitDecision $result */
                $result = Cache::lock($this->getKey($botId) . ':lock', 3)->block(2, $operation);

                return $result;
            } catch (LockTimeoutException) {
                // Best-effort fallback: do not fail the worker, just retry shortly.
                return RateLimitDecision::delayMs(50);
            }
        }

        return $operation();
    }

    public function check(string $botId, Priority $priority): RateLimitDecision
    {
        $effectiveLimit = $this->getEffectiveLimit($priority);
        $timestamps = $this->loadWindow($botId);
        $currentCount = count($timestamps);

        if ($currentCount < $effectiveLimit) {
            return RateLimitDecision::allow();
        }

        // Sliding window: wait until enough oldest events expire so count becomes < effectiveLimit.
        $nowMs = $this->nowMs();
        $index = $currentCount - $effectiveLimit; // 0 => wait for the oldest; 1 => wait for the 2nd oldest; etc.
        $index = max(0, $index);

        $targetTs = $timestamps[$index] ?? $timestamps[0] ?? $nowMs;
        $delayMs = ($targetTs + self::WINDOW_MILLISECONDS) - $nowMs;

        return RateLimitDecision::delayMs(max(0, $delayMs));
    }

    public function record(string $botId): void
    {
        $timestamps = $this->loadWindow($botId);
        $timestamps[] = $this->nowMs();
        $this->saveWindow($botId, $timestamps);
    }

    public function currentUsage(string $botId): int
    {
        return count($this->loadWindow($botId));
    }

    public function remainingCapacity(string $botId, Priority $priority): int
    {
        $current = $this->currentUsage($botId);
        $effectiveLimit = $this->getEffectiveLimit($priority);
        return max(0, $effectiveLimit - $current);
    }

    private function getKey(string $botId): string
    {
        return self::CACHE_KEY_PREFIX . $botId . ':global:window_ms';
    }

    private function getEffectiveLimit(Priority $priority): int
    {
        return match ($priority) {
            Priority::HIGH => $this->rateLimitPerMinute,
            Priority::LOW => max(0, $this->rateLimitPerMinute - $this->reserveHighPerMinute),
        };
    }

    private function nowMs(): int
    {
        if ($this->nowMs !== null) {
            return (int) ($this->nowMs)();
        }

        return (int) floor(microtime(true) * 1000);
    }

    /**
     * @return list<int> timestamps in milliseconds (ascending)
     */
    private function loadWindow(string $botId): array
    {
        $key = $this->getKey($botId);
        $raw = Cache::get($key, []);
        $timestamps = is_array($raw) ? $raw : [];
        $original = $timestamps;

        $nowMs = $this->nowMs();
        $cutoffExclusive = $nowMs - self::WINDOW_MILLISECONDS;

        $timestamps = array_values(array_filter(
            $timestamps,
            static fn (mixed $ts): bool => is_int($ts) && $ts > $cutoffExclusive
        ));

        sort($timestamps, SORT_NUMERIC);

        // Best-effort cleanup to keep cache small (avoid unnecessary writes).
        if ($timestamps !== $original) {
            $this->saveWindow($botId, $timestamps);
        }

        return $timestamps;
    }

    /**
     * @param list<int> $timestamps
     */
    private function saveWindow(string $botId, array $timestamps): void
    {
        Cache::put($this->getKey($botId), $timestamps, self::CACHE_TTL_SECONDS);
    }
}

