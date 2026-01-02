<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Sender;

use HybridGram\Exceptions\Telegram\TelegramOutgoingRateLimited;
use HybridGram\Telegram\Priority;
use HybridGram\Telegram\RateLimiter\OutgoingRateLimiterInterface;
use Phptg\BotApi\MethodInterface;

final class SyncOutgoingDispatcher implements OutgoingDispatcherInterface
{
    public function __construct(
        private readonly OutgoingRateLimiterInterface $rateLimiter,
        private readonly int $maxWaitMs,
    ) {}

    public function dispatch(string $botId, MethodInterface $method, Priority $priority): mixed
    {
        $deadlineMs = $this->nowMs() + $this->maxWaitMs;

        while (true) {
            $decision = $this->rateLimiter->check($botId, $priority);
            if ($decision->allowNow) {
                break;
            }

            $this->waitOrThrow($botId, $decision->delayMilliseconds, $deadlineMs);
        }

        // Use the original Vjik client for actual HTTP call
        $client = new \Phptg\BotApi\TelegramBotApi($this->getTokenForBot($botId));
        $result = $client->call($method);

        // Record successful send
        $this->rateLimiter->record($botId);

        return $result;
    }

    private function waitOrThrow(string $botId, int $delayMilliseconds, int $deadlineMs): void
    {
        $nowMs = $this->nowMs();
        if ($nowMs >= $deadlineMs) {
            throw new TelegramOutgoingRateLimited($botId, 0);
        }

        $remainingMs = $deadlineMs - $nowMs;
        $sleepMs = min(max(0, $delayMilliseconds), $remainingMs);

        // If limiter says "0ms", avoid busy-loop.
        if ($sleepMs <= 0) {
            $sleepMs = min(5, $remainingMs);
        }

        usleep($sleepMs * 1000);
    }

    private function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function getTokenForBot(string $botId): string
    {
        $config = \HybridGram\Core\Config\BotConfig::getBotConfig($botId);
        if ($config === null) {
            throw new \RuntimeException("Bot config not found for botId '{$botId}'.");
        }
        return $config->token;
    }
}

