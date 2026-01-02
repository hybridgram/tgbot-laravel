<?php

declare(strict_types=1);

use HybridGram\Telegram\Priority;
use HybridGram\Telegram\RateLimiter\CacheOutgoingRateLimiter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->t = 0;
    $this->limiter = new CacheOutgoingRateLimiter(
        rateLimitPerMinute: 2,
        reserveHighPerMinute: 0,
        nowMs: fn (): int => (int) $this->t,
    );
});

it('allows when window is empty', function () {
    $decision = $this->limiter->check('bot', Priority::HIGH);

    expect($decision->allowNow)->toBeTrue();
    expect($decision->delayMilliseconds)->toBe(0);
});

it('delays until the oldest request leaves the 60s sliding window', function () {
    $this->t = 0;
    $this->limiter->record('bot');
    $this->t = 1000;
    $this->limiter->record('bot');

    $this->t = 2000;
    $decision = $this->limiter->check('bot', Priority::HIGH);

    expect($decision->allowNow)->toBeFalse();
    expect($decision->delayMilliseconds)->toBe(58_000);
});

it('allows again once the oldest request is strictly older than 60s', function () {
    $this->t = 0;
    $this->limiter->record('bot');
    $this->t = 1000;
    $this->limiter->record('bot');

    // At 60_000ms the oldest is exactly 60s old -> it must be considered expired (strict window).
    $this->t = 60_000;
    $decision = $this->limiter->check('bot', Priority::HIGH);
    expect($decision->allowNow)->toBeTrue();
});

it('when count exceeds limit, delays until enough requests leave the window', function () {
    $this->t = 0;
    $this->limiter->record('bot');
    $this->t = 1000;
    $this->limiter->record('bot');
    $this->t = 2000;
    $this->limiter->record('bot');

    $this->t = 3000;
    $decision = $this->limiter->check('bot', Priority::HIGH);

    // Need 2 expirations to drop from 3 -> < 2, so we wait for the 2nd oldest (1000ms).
    expect($decision->allowNow)->toBeFalse();
    expect($decision->delayMilliseconds)->toBe(58_000);
});

it('applies reserved capacity for low priority', function () {
    $t = 0;
    $limiter = new CacheOutgoingRateLimiter(
        rateLimitPerMinute: 10,
        reserveHighPerMinute: 3, // LOW effective limit = 7
        nowMs: fn (): int => $t,
    );

    for ($i = 0; $i < 7; $i++) {
        $t = $i * 1000;
        $limiter->record('bot');
    }

    $t = 7000;

    $low = $limiter->check('bot', Priority::LOW);
    expect($low->allowNow)->toBeFalse();
    expect($low->delayMilliseconds)->toBe(53_000);

    $high = $limiter->check('bot', Priority::HIGH);
    expect($high->allowNow)->toBeTrue();
});


