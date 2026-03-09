<?php

declare(strict_types=1);

use HybridGram\Core\Routing\ActionType;
use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteGroup;
use HybridGram\Core\Routing\TelegramRouteBuilder;

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

// --- Mutation-killing tests below ---

it('throws when for_bot is missing', function () {
    new RouteGroup([]);
})->throws(InvalidArgumentException::class, 'for_bot parameter is required.');

it('iterates from_state and rejects non-string values', function () {
    // Line 50 ForeachEmptyIterable: the foreach must iterate over items
    // Line 50-53: validates each element is string
    expect(fn () => new RouteGroup([
        'for_bot' => 'test_bot',
        'from_state' => [123],
    ]))->toThrow(InvalidArgumentException::class, 'from_state array must contain only strings.');
});

it('accepts valid from_state with strings iterating over each', function () {
    // Ensures the foreach on line 50 actually processes items (kills ForeachEmptyIterable)
    $group = new RouteGroup([
        'for_bot' => 'test_bot',
        'from_state' => ['state_a', 'state_b'],
    ]);
    expect($group->getAttribute('from_state'))->toBe(['state_a', 'state_b']);
});

it('rejects chat_type that is not ChatType instance and not array', function () {
    // Line 65 InstanceOfToTrue + Line 66 RemoveNot: a string is not ChatType and not array
    expect(fn () => new RouteGroup([
        'for_bot' => 'test_bot',
        'chat_type' => 'invalid_string',
    ]))->toThrow(InvalidArgumentException::class, 'chat_type should be instance of');
});

it('accepts chat_type as array of ChatType instances', function () {
    // Line 65-66: chatType is not instanceof ChatType but IS an array, so passes first check
    $group = new RouteGroup([
        'for_bot' => 'test_bot',
        'chat_type' => [ChatType::PRIVATE, ChatType::GROUP],
    ]);
    expect($group->getAttribute('chat_type'))->toBe([ChatType::PRIVATE, ChatType::GROUP]);
});

it('rejects chat_type array with non-ChatType elements', function () {
    expect(fn () => new RouteGroup([
        'for_bot' => 'test_bot',
        'chat_type' => ['invalid_string'],
    ]))->toThrow(InvalidArgumentException::class, 'chat_type array must contain only instances of');
});

it('iterates middlewares and rejects invalid types', function () {
    // Line 91 ForeachEmptyIterable: must iterate over middleware items
    expect(fn () => new RouteGroup([
        'for_bot' => 'test_bot',
        'middlewares' => [123],
    ]))->toThrow(InvalidArgumentException::class, 'middlewares array must contain only strings or objects.');
});

it('validates send_action must be ActionType instance', function () {
    // Line 98: InstanceOfToTrue, InstanceOfToFalse, RemoveNot
    expect(fn () => new RouteGroup([
        'for_bot' => 'test_bot',
        'send_action' => 'not_an_action_type',
    ]))->toThrow(InvalidArgumentException::class, 'action_type should be instance of');
});

it('accepts valid send_action as ActionType', function () {
    // Line 98: InstanceOfToTrue/InstanceOfToFalse - valid ActionType should pass
    $group = new RouteGroup([
        'for_bot' => 'test_bot',
        'send_action' => ActionType::TYPING,
    ]);
    expect($group->getAttribute('send_action'))->toBe(ActionType::TYPING);
});

function getBuilderRoute(TelegramRouteBuilder $builder): object
{
    $ref = new ReflectionClass($builder);
    $prop = $ref->getProperty('route');
    $prop->setAccessible(true);

    return $prop->getValue($builder);
}

it('addAttributesToBuilder sets forBot on builder', function () {
    $group = new RouteGroup(['for_bot' => 'my_bot']);
    $builder = new TelegramRouteBuilder;

    $result = $group->addAttributesToBuilder($builder);

    $route = getBuilderRoute($result);
    expect($route->botId)->toBe('my_bot');
});

it('addAttributesToBuilder sets fromUserState on builder', function () {
    $group = new RouteGroup(['for_bot' => 'my_bot', 'from_state' => ['state1']]);
    $builder = new TelegramRouteBuilder;

    $result = $group->addAttributesToBuilder($builder);

    $route = getBuilderRoute($result);
    expect($route->fromUserState)->toBe(['state1']);
});

it('addAttributesToBuilder sets chatType for single ChatType', function () {
    $group = new RouteGroup(['for_bot' => 'my_bot', 'chat_type' => ChatType::PRIVATE]);
    $builder = new TelegramRouteBuilder;

    $result = $group->addAttributesToBuilder($builder);

    $route = getBuilderRoute($result);
    expect($route->chatTypes)->toBe([ChatType::PRIVATE]);
});

it('addAttributesToBuilder sets chatTypes for array of ChatType', function () {
    $chatTypes = [ChatType::PRIVATE, ChatType::GROUP];
    $group = new RouteGroup(['for_bot' => 'my_bot', 'chat_type' => $chatTypes]);
    $builder = new TelegramRouteBuilder;

    $result = $group->addAttributesToBuilder($builder);

    $route = getBuilderRoute($result);
    expect($route->chatTypes)->toBe($chatTypes);
});

it('addAttributesToBuilder sets all attributes when all are provided', function () {
    $group = new RouteGroup([
        'for_bot' => 'my_bot',
        'from_state' => ['s1'],
        'to_state' => 'new_state',
        'chat_type' => ChatType::GROUP,
        'cache_key' => 'cache_k',
        'cache_ttl' => 600,
        'middlewares' => ['mw1'],
        'send_action' => ActionType::TYPING,
    ]);

    $builder = new TelegramRouteBuilder;
    $result = $group->addAttributesToBuilder($builder);

    $route = getBuilderRoute($result);
    expect($route->botId)->toBe('my_bot')
        ->and($route->fromUserState)->toBe(['s1'])
        ->and($route->chatTypes)->toBe([ChatType::GROUP])
        ->and($route->actionType)->toBe(ActionType::TYPING);
});

it('addAttributesToBuilder sets resetCallback that re-applies attributes', function () {
    $group = new RouteGroup(['for_bot' => 'my_bot', 'from_state' => ['s1']]);
    $builder = new TelegramRouteBuilder;

    $group->addAttributesToBuilder($builder);

    $ref = new ReflectionClass($builder);
    $cbProp = $ref->getProperty('resetCallback');
    $cbProp->setAccessible(true);
    $callback = $cbProp->getValue($builder);

    expect($callback)->not->toBeNull();

    // Call the reset callback on a fresh builder
    $builder2 = new TelegramRouteBuilder;
    $callback($builder2);

    $route2 = getBuilderRoute($builder2);
    expect($route2->botId)->toBe('my_bot')
        ->and($route2->fromUserState)->toBe(['s1']);
});
