<?php

declare(strict_types=1);

use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Http\Middlewares\SetStateTelegramRouteMiddleware;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

beforeEach(function () {
    $this->chat = new Chat(id: 123, type: 'private');
    $this->user = new User(id: 456, isBot: false, firstName: 'Test');
    $this->message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $this->user,
        text: 'test message'
    );
    $this->update = new Update(updateId: 1, message: $this->message);
    
    $this->stateManager = Mockery::mock(StateManagerInterface::class);
    $this->app->instance(StateManagerInterface::class, $this->stateManager);
});

it('sets state for chat after processing', function () {
    $this->stateManager->shouldReceive('setChatState')
        ->with($this->chat, 'new_state', null, null)
        ->once();
    
    $middleware = new SetStateTelegramRouteMiddleware('new_state', ttl: null, useUserState: false);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('sets state for user after processing', function () {
    $this->stateManager->shouldReceive('setUserState')
        ->with($this->chat, $this->user, 'new_user_state', null, null)
        ->once();
    
    $middleware = new SetStateTelegramRouteMiddleware('new_user_state', ttl: null, useUserState: true);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('sets state for chat with custom ttl', function () {
    $this->stateManager->shouldReceive('setChatState')
        ->with($this->chat, 'new_state', 3600, null)
        ->once();
    
    $middleware = new SetStateTelegramRouteMiddleware('new_state', ttl: 3600, useUserState: false);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('sets state for user with custom ttl', function () {
    $this->stateManager->shouldReceive('setUserState')
        ->with($this->chat, $this->user, 'new_user_state', 1800, null)
        ->once();
    
    $middleware = new SetStateTelegramRouteMiddleware('new_user_state', ttl: 1800, useUserState: true);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('sets state for chat with data', function () {
    $this->stateManager->shouldReceive('setChatState')
        ->with($this->chat, 'new_state', null, 5)
        ->once();
    
    $middleware = new SetStateTelegramRouteMiddleware('new_state', ttl: null, useUserState: false, data: 5);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('sets state for user with data', function () {
    $this->stateManager->shouldReceive('setUserState')
        ->with($this->chat, $this->user, 'new_user_state', null, ['quiz_id' => 5])
        ->once();
    
    $middleware = new SetStateTelegramRouteMiddleware('new_user_state', ttl: null, useUserState: true, data: ['quiz_id' => 5]);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});
