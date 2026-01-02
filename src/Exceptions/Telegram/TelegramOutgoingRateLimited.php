<?php

declare(strict_types=1);

namespace HybridGram\Exceptions\Telegram;

use RuntimeException;

final class TelegramOutgoingRateLimited extends RuntimeException
{
    public function __construct(
        string $botId,
        int $delayMilliseconds,
        ?\Throwable $previous = null,
    ) {
        $seconds = (int) ceil(max(0, $delayMilliseconds) / 1000);
        parent::__construct(
            "Telegram outgoing rate limit exceeded for bot '{$botId}'. Wait ~{$seconds}s ({$delayMilliseconds}ms).",
            0,
            $previous,
        );
    }
}

