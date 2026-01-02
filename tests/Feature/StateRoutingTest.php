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
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $this->user,
        text: '/start'
    );
    $this->update = new Update(134, message: $this->message);
});

it('routes to correct handler based on state', function () {
    // Устанавливаем стейт для чата
    $this->stateManager->shouldReceive('getChatState')
        ->with($this->chat)
        ->andReturn(new State('waiting_for_name'));
    
    // Также мокируем getStateForUser (теперь вызывается всегда)
    $this->stateManager->shouldReceive('getUserState')
        ->with($this->chat, $this->user)
        ->andReturn(null);
    
    // Регистрируем роут для стейта
    $this->router->forBot('test_bot')
        ->chatType(ChatType::PRIVATE)
        ->fromChatState(['waiting_for_name'])
        ->onCommand('start', function ($data) {
            return 'name_handler';
        });
    
    $route = $this->router->resolveActionsByUpdate(
        $this->update,
        'test_bot'
    );
    
    // Проверяем, что роут найден
    expect($route)->not->toBeNull();
    expect($route->fromChatState)->toBe(['waiting_for_name']);
    expect($route->type)->toBe(RouteType::COMMAND);
    expect($route->botId)->toBe('test_bot');
});

it('does not route to handler when state does not match', function () {
    // Устанавливаем другой стейт
    $this->stateManager->shouldReceive('getChatState')
        ->with($this->chat)
        ->andReturn(new State('different_state'));
    
    // Также мокируем getStateForUser
    $this->stateManager->shouldReceive('getUserState')
        ->with($this->chat, $this->user)
        ->andReturn(null);
    
    // Регистрируем роут для другого стейта
    $this->router->forBot('test_bot')
        ->chatType(ChatType::PRIVATE)
        ->fromChatState(['waiting_for_name'])
        ->onCommand('start', function ($data) {
            return 'name_handler';
        });
    
    // Должен найти fallback роут
    $router = $this->router;
    $update = $this->update;
    
    $route = $router->resolveActionsByUpdate(
        $update,
        'test_bot'
    );
    
    expect($route->type)->toBe(RouteType::FALLBACK);
});

it('routes to handler without state requirement', function () {
    // Не устанавливаем стейт
    $this->stateManager->shouldReceive('getChatState')
        ->with($this->chat)
        ->andReturn(null);
    
    // Также мокируем getStateForUser
    $this->stateManager->shouldReceive('getUserState')
        ->with($this->chat, $this->user)
        ->andReturn(null);
    
    // Регистрируем роут без требования стейта
    $this->router->forBot('test_bot')
        ->chatType(ChatType::PRIVATE)
        ->onCommand('start', function ($data) {
            return 'general_handler';
        });
    
    $route = $this->router->resolveActionsByUpdate(
        $this->update,
        'test_bot'
    );
    
    expect($route->fromChatState)->toBeNull();
});
