<?php

declare(strict_types=1);

use HybridGram\Telegram\RateLimiter\RateLimitDecision;

covers(RateLimitDecision::class);

it('creates an allow decision', function () {
    $decision = RateLimitDecision::allow();

    expect($decision->allowNow)->toBeTrue();
    expect($decision->delayMilliseconds)->toBe(0);
});

it('creates a delay decision with given milliseconds', function () {
    $decision = RateLimitDecision::delayMs(500);

    expect($decision->allowNow)->toBeFalse();
    expect($decision->delayMilliseconds)->toBe(500);
});

it('clamps negative delay to zero', function () {
    $decision = RateLimitDecision::delayMs(-100);

    expect($decision->allowNow)->toBeFalse();
    expect($decision->delayMilliseconds)->toBe(0);
});

it('returns zero delay for zero milliseconds', function () {
    $decision = RateLimitDecision::delayMs(0);

    expect($decision->allowNow)->toBeFalse();
    expect($decision->delayMilliseconds)->toBe(0);
});
