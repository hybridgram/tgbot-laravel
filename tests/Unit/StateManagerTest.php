<?php

declare(strict_types=1);

use HybridGram\Core\State\State;
use HybridGram\Core\State\StateManager;
use Illuminate\Support\Facades\Cache;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\User;

beforeEach(function () {
    $this->stateManager = new StateManager;
    $this->chat = new Chat(id: 123, type: 'private');
    $this->user = new User(id: 456, isBot: false, firstName: 'Test');
});

it('can set and get state for chat', function () {
    $this->stateManager->setChatState($this->chat, 'test_state');

    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('test_state')
        ->and($state->hasData())->toBeFalse()
        ->and($this->stateManager->isChatInState($this->chat, 'test_state'))->toBeTrue()
        ->and($this->stateManager->isChatInState($this->chat, 'other_state'))->toBeFalse();
});

it('can set and get state for user in chat', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'user_state');

    $state = $this->stateManager->getUserState($this->chat, $this->user);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('user_state')
        ->and($state->hasData())->toBeFalse()
        ->and($this->stateManager->isUserInState($this->chat, $this->user, 'user_state'))->toBeTrue()
        ->and($this->stateManager->isUserInState($this->chat, $this->user, 'other_state'))->toBeFalse();
});

it('can clear state for chat', function () {
    $this->stateManager->setChatState($this->chat, 'test_state');
    $this->stateManager->clearChatState($this->chat);

    expect($this->stateManager->getChatState($this->chat))->toBeNull();
});

it('can clear state for user in chat', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'user_state');
    $this->stateManager->clearUserState($this->chat, $this->user);

    expect($this->stateManager->getUserState($this->chat, $this->user))->toBeNull();
});

it('can set state with custom ttl', function () {
    $this->stateManager->setChatState($this->chat, 'test_state', 3600);

    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('test_state');
});

it('can set user state with custom ttl', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'user_state', 1800);

    $state = $this->stateManager->getUserState($this->chat, $this->user);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('user_state');
});

it('can set state with data', function () {
    $this->stateManager->setChatState($this->chat, 'create_quiz', null, [5]);

    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('create_quiz')
        ->and($state->hasData())->toBeTrue()
        ->and($state->getData())->toBe([5]);
});

it('can set user state with data', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'create_quiz', null, ['quiz_id' => 5]);

    $state = $this->stateManager->getUserState($this->chat, $this->user);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('create_quiz')
        ->and($state->hasData())->toBeTrue()
        ->and($state->getData())->toBe(['quiz_id' => 5]);
});

// --- Mutation-killing tests below ---

it('uses default TTL when null is passed for setChatState', function () {
    // Line 35: IfNegated - when ttl is null, default TTL (86400) should be used
    // We verify by spying on Cache::put
    Cache::shouldReceive('put')
        ->once()
        ->withArgs(function ($key, $value, $ttl) {
            return $ttl === 86400; // default CACHE_TTL
        });

    $this->stateManager->setChatState($this->chat, 'test_state', null);
});

it('uses provided TTL when non-null is passed for setChatState', function () {
    // Line 35: IfNegated - when ttl is NOT null, provided TTL should be used
    Cache::shouldReceive('put')
        ->once()
        ->withArgs(function ($key, $value, $ttl) {
            return $ttl === 7200;
        });

    $this->stateManager->setChatState($this->chat, 'test_state', 7200);
});

it('uses default TTL when null is passed for setUserState', function () {
    // Line 46: IfNegated - when ttl is null, default TTL (86400) should be used
    Cache::shouldReceive('put')
        ->once()
        ->withArgs(function ($key, $value, $ttl) {
            return $ttl === 86400;
        });

    $this->stateManager->setUserState($this->chat, $this->user, 'user_state', null);
});

it('uses provided TTL when non-null is passed for setUserState', function () {
    // Line 46: IfNegated - when ttl is NOT null, provided TTL should be used
    Cache::shouldReceive('put')
        ->once()
        ->withArgs(function ($key, $value, $ttl) {
            return $ttl === 1800;
        });

    $this->stateManager->setUserState($this->chat, $this->user, 'user_state', 1800);
});

it('generates correct cache key for chat state', function () {
    // Line 101: ConcatRemoveRight, ConcatSwitchSides
    // The key must be "telegram_state_chat_" + chat.id
    Cache::shouldReceive('put')
        ->once()
        ->withArgs(function ($key, $value, $ttl) {
            return $key === 'telegram_state_chat_123';
        });

    $this->stateManager->setChatState($this->chat, 'test_state');
});

it('generates correct cache key for user state', function () {
    // Line 106: ConcatRemoveLeft (x2), ConcatRemoveRight (x3), ConcatSwitchSides (x3)
    // The key must be "telegram_state_user_" + chat.id + "_" + user.id
    Cache::shouldReceive('put')
        ->once()
        ->withArgs(function ($key, $value, $ttl) {
            return $key === 'telegram_state_user_123_456';
        });

    $this->stateManager->setUserState($this->chat, $this->user, 'user_state');
});

it('generates different keys for different chats', function () {
    // Further kills ConcatRemoveRight/ConcatSwitchSides on line 101
    $chat2 = new Chat(id: 999, type: 'private');

    $this->stateManager->setChatState($this->chat, 'state_a');
    $this->stateManager->setChatState($chat2, 'state_b');

    $stateA = $this->stateManager->getChatState($this->chat);
    $stateB = $this->stateManager->getChatState($chat2);

    expect($stateA->getName())->toBe('state_a')
        ->and($stateB->getName())->toBe('state_b');
});

it('generates different keys for different users in same chat', function () {
    // Further kills ConcatRemoveLeft/ConcatRemoveRight/ConcatSwitchSides on line 106
    $user2 = new User(id: 789, isBot: false, firstName: 'Test2');

    $this->stateManager->setUserState($this->chat, $this->user, 'user_state_a');
    $this->stateManager->setUserState($this->chat, $user2, 'user_state_b');

    $stateA = $this->stateManager->getUserState($this->chat, $this->user);
    $stateB = $this->stateManager->getUserState($this->chat, $user2);

    expect($stateA->getName())->toBe('user_state_a')
        ->and($stateB->getName())->toBe('user_state_b');
});

it('deserializeState returns null for array without name key', function () {
    // Line 122: BooleanAndToBooleanOr - is_array($stored) && isset($stored['name'])
    // When stored is an array but has no 'name' key, should return null
    Cache::shouldReceive('get')
        ->once()
        ->andReturn(['data' => 'some_data']); // array but no 'name'

    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeNull();
});

it('deserializeState returns null for non-array stored value', function () {
    // Line 122: BooleanAndToBooleanOr - when stored is not an array
    Cache::shouldReceive('get')
        ->once()
        ->andReturn('just_a_string');

    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeNull();
});

it('deserializeState creates State from valid stored data', function () {
    // Line 122: ensures both conditions must be true
    Cache::shouldReceive('get')
        ->once()
        ->andReturn(['name' => 'my_state', 'data' => ['key' => 'val']]);

    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeInstanceOf(State::class)
        ->and($state->getName())->toBe('my_state')
        ->and($state->getData())->toBe(['key' => 'val']);
});

it('isChatInAnyState returns false when no state is set', function () {
    expect($this->stateManager->isChatInAnyState($this->chat, ['state1', 'state2']))->toBeFalse();
});

it('isChatInAnyState returns true when chat is in one of the states', function () {
    $this->stateManager->setChatState($this->chat, 'state2');
    expect($this->stateManager->isChatInAnyState($this->chat, ['state1', 'state2']))->toBeTrue();
});

it('isChatInAnyState returns false when chat is in a different state', function () {
    $this->stateManager->setChatState($this->chat, 'state3');
    expect($this->stateManager->isChatInAnyState($this->chat, ['state1', 'state2']))->toBeFalse();
});

it('isUserInAnyState returns false when no state is set', function () {
    expect($this->stateManager->isUserInAnyState($this->chat, $this->user, ['state1', 'state2']))->toBeFalse();
});

it('isUserInAnyState returns true when user is in one of the states', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'state2');
    expect($this->stateManager->isUserInAnyState($this->chat, $this->user, ['state1', 'state2']))->toBeTrue();
});

it('isUserInAnyState returns false when user is in a different state', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'state3');
    expect($this->stateManager->isUserInAnyState($this->chat, $this->user, ['state1', 'state2']))->toBeFalse();
});

it('isChatInState returns false when no state is set', function () {
    expect($this->stateManager->isChatInState($this->chat, 'some_state'))->toBeFalse();
});

it('isUserInState returns false when no state is set', function () {
    expect($this->stateManager->isUserInState($this->chat, $this->user, 'some_state'))->toBeFalse();
});
