<?php

declare(strict_types=1);

use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Http\Middlewares\CheckStateTelegramRouteMiddleware;
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

it('allows processing when chat is in required state', function () {
    $this->stateManager->shouldReceive('isChatInAnyState')
        ->with($this->chat, ['required_state'])
        ->andReturn(true);
    
    $middleware = new CheckStateTelegramRouteMiddleware(['required_state'], false);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('blocks processing when chat is not in required state', function () {
    $this->stateManager->shouldReceive('isChatInAnyState')
        ->with($this->chat, ['required_state'])
        ->andReturn(false);
    
    $middleware = new CheckStateTelegramRouteMiddleware(['required_state'], false);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBeNull();
});

it('allows processing when user is in required state', function () {
    $this->stateManager->shouldReceive('isUserInAnyState')
        ->with($this->chat, $this->user, ['required_state'])
        ->andReturn(true);
    
    $middleware = new CheckStateTelegramRouteMiddleware(['required_state'], true);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBe('processed');
});

it('blocks processing when user is not in required state', function () {
    $this->stateManager->shouldReceive('isUserInAnyState')
        ->with($this->chat, $this->user, ['required_state'])
        ->andReturn(false);
    
    $middleware = new CheckStateTelegramRouteMiddleware(['required_state'], true);
    $next = function ($update) {
        return 'processed';
    };
    
    $result = $middleware->handle($this->update, $next);
    
    expect($result)->toBeNull();
});
