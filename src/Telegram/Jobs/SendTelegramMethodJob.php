<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Jobs;

use HybridGram\Exceptions\Telegram\TelegramOutgoingRateLimited;
use HybridGram\Exceptions\Telegram\TelegramRequestError;
use HybridGram\Telegram\Priority;
use HybridGram\Telegram\RateLimiter\OutgoingRateLimiterInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\MethodInterface;

final class SendTelegramMethodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $botId Bot identifier
     * @param MethodInterface $method Telegram API method (must be serializable, no InputFile with resource)
     * @param Priority $priority Request priority
     */
    public function __construct(
        public readonly string $botId,
        public readonly MethodInterface $method,
        public readonly Priority $priority,
        public readonly int $sequence,
    ) {
        // Validate that method doesn't contain non-serializable resources
        $this->validateMethod($method);
    }

    public function handle(): void
    {
        $gate = $this->fifoGateEnter();
        if ($gate['action'] === 'skip') {
            return;
        }
        if ($gate['action'] === 'delay') {
            if (isset($this->job)) {
                $this->release(now()->addMilliseconds($gate['delayMs']));
                return;
            }
            throw new TelegramOutgoingRateLimited($this->botId, $gate['delayMs']);
        }

        $rateLimiter = App::make(OutgoingRateLimiterInterface::class);
        $decision = $rateLimiter->acquire($this->botId, $this->priority);

        if (!$decision->allowNow) {
            // Do not block a worker; reschedule the job for when a slot is expected to be available.
            if (isset($this->job)) {
                $this->fifoGateExit();
                $this->release(now()->addMilliseconds($decision->delayMilliseconds));

                return;
            }

            // Fallback (should not happen in real queue mode): fail fast.
            throw new TelegramOutgoingRateLimited($this->botId, $decision->delayMilliseconds);
        }

        try {
            $client = new \Phptg\BotApi\TelegramBotApi($this->getTokenForBot($this->botId));
            $result = $client->call($this->method);

            if ($result instanceof FailResult) {
                $this->reportFailResult($result);

                // IMPORTANT: always advance FIFO pointer, otherwise a single 400 will block the whole queue.
                $this->fifoGateAdvance();


                $error = TelegramRequestError::fromFailResult($result);

                // In real queue mode: mark as failed (visible in worker logs) without retries.
                if (isset($this->job)) {
                    $this->fail($error);
                    return;
                }

                // Fallback: throw if executed synchronously.
                throw $error;
            }
        } catch (\Throwable $e) {
            $this->fifoGateExit();
            throw $e;
        }

        $this->fifoGateAdvance();
    }

    private function reportFailResult(FailResult $failResult): void
    {
        if (! (bool) config('hybridgram.sending.log_failures', true)) {
            return;
        }

        $context = [
            'bot_id' => $this->botId,
            'priority' => $this->priority->value,
            'sequence' => $this->sequence,
            'method' => $failResult->method->getApiMethod(),
            'error_code' => $failResult->errorCode,
            'description' => $failResult->description,
            'status_code' => $failResult->response->statusCode ?? null,
        ];

        if ((bool) config('hybridgram.sending.log_response_body', true)) {
            $context['telegram_response'] = $failResult->response->body ?? null;
        }

        logger()->error('Telegram queued outgoing request failed', $context);
    }

    private function getTokenForBot(string $botId): string
    {
        $config = \HybridGram\Core\Config\BotConfig::getBotConfig($botId);
        if ($config === null) {
            throw new \RuntimeException("Bot config not found for botId '{$botId}'.");
        }

        return $config->token;
    }

    /**
     * Strict FIFO gate:
     * - only the expected sequence is allowed to proceed (per bot + priority)
     * - while a job is executing, we keep a short-lived "processing" marker
     *   to avoid concurrent duplicates sending out of order.
     *
     * @return array{action:'proceed'|'delay'|'skip', delayMs:int}
     */
    private function fifoGateEnter(): array
    {
        $nextKey = $this->fifoNextKey();
        $processingKey = $this->fifoProcessingKey();
        $delayMs = 100;

        return $this->withFifoLock(function () use ($nextKey, $processingKey, $delayMs): array {
            // Initialize "next" pointer if missing.
            Cache::add($nextKey, 1, 60 * 60 * 24 * 30);

            $expected = (int) Cache::get($nextKey, 1);

            if ($this->sequence < $expected) {
                // Already passed (duplicate retry) — drop without sending.
                return ['action' => 'skip', 'delayMs' => 0];
            }

            if ($this->sequence > $expected) {
                return ['action' => 'delay', 'delayMs' => $delayMs];
            }

            $processing = Cache::get($processingKey);
            if ($processing !== null && (int) $processing !== $this->sequence) {
                return ['action' => 'delay', 'delayMs' => $delayMs];
            }

            // Mark in-flight (TTL protects from worker crashes).
            Cache::put($processingKey, $this->sequence, 30);

            return ['action' => 'proceed', 'delayMs' => 0];
        });
    }

    private function fifoGateExit(): void
    {
        $processingKey = $this->fifoProcessingKey();

        $this->withFifoLock(function () use ($processingKey): void {
            $processing = Cache::get($processingKey);
            if ($processing !== null && (int) $processing === $this->sequence) {
                Cache::forget($processingKey);
            }
        });
    }

    private function fifoGateAdvance(): void
    {
        $nextKey = $this->fifoNextKey();
        $processingKey = $this->fifoProcessingKey();

        $this->withFifoLock(function () use ($nextKey, $processingKey): void {
            $expected = (int) Cache::get($nextKey, 1);
            $next = max($expected, $this->sequence + 1);

            Cache::put($nextKey, $next, 60 * 60 * 24 * 30);

            $processing = Cache::get($processingKey);
            if ($processing !== null && (int) $processing === $this->sequence) {
                Cache::forget($processingKey);
            }
        });
    }

    private function fifoLockKey(): string
    {
        return "tg:out:{$this->botId}:{$this->priority->value}:fifo:lock";
    }

    private function fifoNextKey(): string
    {
        return "tg:out:{$this->botId}:{$this->priority->value}:fifo:next";
    }

    private function fifoProcessingKey(): string
    {
        return "tg:out:{$this->botId}:{$this->priority->value}:fifo:processing";
    }

    /**
     * @template T
     * @param \Closure():T $fn
     * @return T
     */
    private function withFifoLock(\Closure $fn): mixed
    {
        $store = Cache::getStore();
        if ($store instanceof LockProvider) {
            return Cache::lock($this->fifoLockKey(), 5)->block(2, $fn);
        }

        // Best-effort fallback (no strictness guarantee).
        return $fn();
    }

    private function validateMethod(MethodInterface $method): void
    {
        $data = $method->getData();
        $this->checkForNonSerializable($data, $method->getApiMethod());
    }

    private function checkForNonSerializable(mixed $value, string $context): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->checkForNonSerializable($item, "{$context}.{$key}");
            }
            return;
        }

        if (is_object($value)) {
            // Check for InputFile with resource (not serializable)
            if ($value instanceof \Phptg\BotApi\Type\InputFile) {
                if (is_resource($value->resource)) {
                    throw new \RuntimeException(
                        "Method '{$context}' contains InputFile with resource stream, which cannot be queued. " .
                        "Use sync mode (disable queue) or convert file to file path before queuing."
                    );
                }
            }

            // Check for other non-serializable types
            if ($value instanceof \Closure) {
                throw new \RuntimeException(
                    "Method '{$context}' contains Closure, which cannot be queued."
                );
            }

            // Recursively check object properties if it's a standard object
            if ($value instanceof \stdClass) {
                $this->checkForNonSerializable((array) $value, $context);
            }
        }
    }
}

