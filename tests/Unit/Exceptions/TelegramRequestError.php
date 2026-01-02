<?php

declare(strict_types=1);

use HybridGram\Exceptions\Telegram\TelegramRequestError;
use Phptg\BotApi\MethodInterface;
use Phptg\BotApi\Transport\ApiResponse;
use Phptg\BotApi\Type\ResponseParameters;

test('creates telegram request error with basic information', function () {
    $method = Mockery::mock(MethodInterface::class);
    $method->shouldReceive('getApiMethod')->andReturn('getUpdates');

    $response = new ApiResponse(400, '{"ok":false,"error_code":400,"description":"Bad Request"}');

    $error = new TelegramRequestError($method, $response, 'Bad Request', null, 400);

    expect($error->getMessage())->toContain("Telegram API request failed for method 'getUpdates'")
        ->and($error->getMessage())->toContain('with error code 400')
        ->and($error->getMessage())->toContain(': Bad Request')
        ->and($error->getMessage())->toContain('(HTTP 400)')
        ->and($error->getCode())->toBe(400);
});

test('creates telegram request error with response parameters', function () {
    $method = Mockery::mock(MethodInterface::class);
    $method->shouldReceive('getApiMethod')->andReturn('sendMessage');

    $response = new ApiResponse(429, '{"ok":false,"error_code":429,"description":"Too Many Requests"}');
    $parameters = new ResponseParameters(null, 60);

    $error = new TelegramRequestError($method, $response, 'Too Many Requests', $parameters, 429);

    expect($error->getMessage())->toContain("Telegram API request failed for method 'sendMessage'")
        ->and($error->getMessage())->toContain('with error code 429')
        ->and($error->getMessage())->toContain(': Too Many Requests')
        ->and($error->getMessage())->toContain('(HTTP 429)')
        ->and($error->getMessage())->toContain('[Parameters: retry_after: 60]');
});

test('creates telegram request error with both parameters', function () {
    $method = Mockery::mock(MethodInterface::class);
    $method->shouldReceive('getApiMethod')->andReturn('sendMessage');

    $response = new ApiResponse(429, '{"ok":false,"error_code":429,"description":"Too Many Requests"}');
    $parameters = new ResponseParameters(12345, 30);

    $error = new TelegramRequestError($method, $response, 'Too Many Requests', $parameters, 429);

    expect($error->getMessage())->toContain('[Parameters: migrate_to_chat_id: 12345, retry_after: 30]');
});
