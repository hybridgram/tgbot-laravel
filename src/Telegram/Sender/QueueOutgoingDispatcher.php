<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Sender;

use HybridGram\Telegram\Jobs\SendTelegramMethodJob;
use HybridGram\Telegram\Priority;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Phptg\BotApi\MethodInterface;

final class QueueOutgoingDispatcher implements OutgoingDispatcherInterface
{
    /**
     * @param array<string, string> $queueNames Map of priority to queue name
     */
    public function __construct(
        private readonly array $queueNames,
    ) {}

    public function dispatch(string $botId, MethodInterface $method, Priority $priority): void
    {
        $queueName = $this->queueNames[$priority->value] ?? $this->queueNames['high'];
        $sequence = $this->nextSequence($botId, $priority);
        $job = new SendTelegramMethodJob($botId, $method, $priority, $sequence);

        // Always enqueue immediately; workers will rate-limit at execution time (release if no slot).
        Queue::pushOn($queueName, $job);
    }

    private function nextSequence(string $botId, Priority $priority): int
    {
        $key = "tg:out:{$botId}:{$priority->value}:seq";

        return (int) Cache::increment($key);
    }
}

