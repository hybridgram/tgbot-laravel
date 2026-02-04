<?php

declare(strict_types=1);

use HybridGram\Core\Routing\ActionType;
use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteGroup;

it('creates route group with valid attributes', function () {
    $attributes = [
        'for_bot' => 'test_bot',
        'from_state' => ['initial_state'],
        'chat_type' => ChatType::PRIVATE,
        'middlewares' => ['auth', 'throttle'],
        'action_type' => ActionType::TYPING,
        'cache_ttl' => 3600,
        'cache_key' => 'test_cache_key',
    ];

    $group = new RouteGroup($attributes);

    expect($group)->toBeInstanceOf(RouteGroup::class)
        ->and($group->getAttribute('for_bot'))->toBe('test_bot')
        ->and($group->getAttribute('from_state'))->toBe(['initial_state'])
        ->and($group->getAttribute('chat_type'))->toBe(ChatType::PRIVATE)
        ->and($group->getAttribute('middlewares'))->toBe(['auth', 'throttle'])
        ->and($group->getAttribute('action_type'))->toBe(ActionType::TYPING)
        ->and($group->getAttribute('cache_ttl'))->toBe(3600)
        ->and($group->getAttribute('cache_key'))->toBe('test_cache_key');
});

it('supports all chat types', function () {
    $chatTypes = [ChatType::PRIVATE, ChatType::GROUP, ChatType::SUPERGROUP, ChatType::CHANNEL];

    foreach ($chatTypes as $chatType) {
        $attributes = [
            'for_bot' => 'test_bot',
            'chat_type' => $chatType,
        ];

        $group = new RouteGroup($attributes);
        expect($group->getAttribute('chat_type'))->toBe($chatType);
    }
});

it('validates from_state as array of strings', function () {
    $attributes = [
        'for_bot' => 'test_bot',
        'from_state' => ['state1', 'state2', 'state3'],
    ];

    $group = new RouteGroup($attributes);
    expect($group)->toBeInstanceOf(RouteGroup::class)
        ->and($group->getAttribute('from_state'))->toBe(['state1', 'state2', 'state3']);
});

it('validates middlewares with objects', function () {
    $middlewareObject = new class {};

    $attributes = [
        'for_bot' => 'test_bot',
        'middlewares' => ['auth', $middlewareObject],
    ];

    $group = new RouteGroup($attributes);
    expect($group)->toBeInstanceOf(RouteGroup::class)
        ->and($group->getAttribute('middlewares'))->toBe(['auth', $middlewareObject]);
});
