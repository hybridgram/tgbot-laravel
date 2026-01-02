<?php

declare(strict_types=1);

use HybridGram\Core\State\State;
use HybridGram\Core\State\StateManager;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\User;

beforeEach(function () {
    $this->stateManager = new StateManager();
    $this->chat = new Chat(id: 123, type: 'private');
    $this->user = new User(id: 456, isBot: false, firstName: 'Test');
});

it('can set and get state for chat', function () {
    $this->stateManager->setChatState($this->chat, 'test_state');
    
    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeInstanceOf(State::class);
    expect($state->getName())->toBe('test_state');
    expect($state->hasData())->toBeFalse();
    expect($this->stateManager->isChatInState($this->chat, 'test_state'))->toBeTrue();
    expect($this->stateManager->isChatInState($this->chat, 'other_state'))->toBeFalse();
});

it('can set and get state for user in chat', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'user_state');
    
    $state = $this->stateManager->getUserState($this->chat, $this->user);
    expect($state)->toBeInstanceOf(State::class);
    expect($state->getName())->toBe('user_state');
    expect($state->hasData())->toBeFalse();
    expect($this->stateManager->isUserInState($this->chat, $this->user, 'user_state'))->toBeTrue();
    expect($this->stateManager->isUserInState($this->chat, $this->user, 'other_state'))->toBeFalse();
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
    expect($state)->toBeInstanceOf(State::class);
    expect($state->getName())->toBe('test_state');
});

it('can set user state with custom ttl', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'user_state', 1800);
    
    $state = $this->stateManager->getUserState($this->chat, $this->user);
    expect($state)->toBeInstanceOf(State::class);
    expect($state->getName())->toBe('user_state');
});

it('can set state with data', function () {
    $this->stateManager->setChatState($this->chat, 'create_quiz', null, 5);
    
    $state = $this->stateManager->getChatState($this->chat);
    expect($state)->toBeInstanceOf(State::class);
    expect($state->getName())->toBe('create_quiz');
    expect($state->hasData())->toBeTrue();
    expect($state->getData())->toBe(5);
});

it('can set user state with data', function () {
    $this->stateManager->setUserState($this->chat, $this->user, 'create_quiz', null, ['quiz_id' => 5]);
    
    $state = $this->stateManager->getUserState($this->chat, $this->user);
    expect($state)->toBeInstanceOf(State::class);
    expect($state->getName())->toBe('create_quiz');
    expect($state->hasData())->toBeTrue();
    expect($state->getData())->toBe(['quiz_id' => 5]);
});
