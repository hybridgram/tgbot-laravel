<?php

declare(strict_types=1);

use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\Routing\TelegramRouter;
use HybridGram\Core\State\State;
use HybridGram\Core\State\StateManagerInterface;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

beforeEach(function () {
    $this->router = app(TelegramRouter::class);
    $this->stateManager = Mockery::mock(StateManagerInterface::class);
    $this->app->instance(StateManagerInterface::class, $this->stateManager);

    $this->chat = new Chat(id: 123, type: 'private');
    $this->user = new User(id: 456, isBot: false, firstName: 'Test');
    $this->message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: $this->chat,
        from: $this->user,
        text: '/help'
    );
    $this->update = new Update(134, message: $this->message);
});

it('routes to handler without fromChatState from any state', function () {
    // Set state for chat
    $this->stateManager->shouldReceive('getChatState')
        ->with($this->chat)
        ->andReturn(new State('some_state'));

    // Also mock getStateForUser
    $this->stateManager->shouldReceive('getUserState')
        ->with($this->chat, $this->user)
        ->andReturn(null);

    // Register route WITHOUT fromChatState - should work from any state
    $this->router->forBot('test_bot')
        ->chatType(ChatType::PRIVATE)
        ->onCommand('help', function ($data) {
            return 'help_handler';
        });

    $route = $this->router->resolveActionsByUpdate(
        $this->update,
        'test_bot'
    );

    // Verify that the route was found
    expect($route)->not->toBeNull();
    expect($route->fromChatState)->toBeNull(); // Should be null for route without fromChatState
    expect($route->type)->toBe(RouteType::COMMAND);
    expect($route->botId)->toBe('test_bot');
});

it('routes to handler without fromChatState when no state is set', function () {
    // Don't set state (null)
    $this->stateManager->shouldReceive('getChatState')
        ->with($this->chat)
        ->andReturn(null);

    // Also mock getStateForUser
    $this->stateManager->shouldReceive('getUserState')
        ->with($this->chat, $this->user)
        ->andReturn(null);

    // Register route WITHOUT fromChatState
    $this->router->forBot('test_bot')
        ->chatType(ChatType::PRIVATE)
        ->onCommand('help', function ($data) {
            return 'help_handler';
        });

    $route = $this->router->resolveActionsByUpdate(
        $this->update,
        'test_bot'
    );

    // Verify that the route was found
    expect($route)->not->toBeNull();
    expect($route->fromChatState)->toBeNull();
    expect($route->type)->toBe(RouteType::COMMAND);
    expect($route->botId)->toBe('test_bot');
});
