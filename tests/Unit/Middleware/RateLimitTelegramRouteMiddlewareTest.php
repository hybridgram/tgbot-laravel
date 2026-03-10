<?php

declare(strict_types=1);

use HybridGram\Http\Middlewares\RateLimitTelegramRouteMiddleware;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

it('allows requests under limit', function () {
    $user = new User(id: 100, isBot: false, firstName: 'Test');
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: new Chat(id: 1, type: 'private'),
        from: $user,
        text: 'hi'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new RateLimitTelegramRouteMiddleware(maxRequests: 2, timeWindow: 60);
    $next = fn (Update $u) => 'ok';

    expect($middleware->handle($update, $next))->toBe('ok');
    expect($middleware->handle($update, $next))->toBe('ok');
});

it('returns null when rate limit exceeded', function () {
    $user = new User(id: 200, isBot: false, firstName: 'Test');
    $message = new Message(
        messageId: 1,
        date: new DateTimeImmutable,
        chat: new Chat(id: 1, type: 'private'),
        from: $user,
        text: 'hi'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new RateLimitTelegramRouteMiddleware(maxRequests: 2, timeWindow: 60);
    $next = fn (Update $u) => 'ok';

    $middleware->handle($update, $next);
    $middleware->handle($update, $next);
    $result = $middleware->handle($update, $next);

    expect($result)->toBeNull();
});

it('passes through update without user', function () {
    $update = new Update(updateId: 1);

    $middleware = new RateLimitTelegramRouteMiddleware(maxRequests: 1, timeWindow: 60);
    $next = fn (Update $u) => 'ok';

    expect($middleware->handle($update, $next))->toBe('ok');
});

it('default maxRequests is exactly 10', function () {
    $user = new User(id: 400, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 1, type: 'private');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'hi');
    $update = new Update(updateId: 1, message: $message);

    $middleware = new RateLimitTelegramRouteMiddleware;
    $next = fn (Update $u) => 'ok';

    for ($i = 0; $i < 10; $i++) {
        expect($middleware->handle($update, $next))->toBe('ok');
    }

    expect($middleware->handle($update, $next))->toBeNull();
});

it('default timeWindow allows requests older than 60 seconds to expire', function () {
    // Тест проверяет, что дефолтный timeWindow = 60, а не 59 и не 61.
    // Логика фильтра: $now - $timestamp < $timeWindow
    // Запрос сделанный ровно 60 сек назад: 60 < 60 = false → истёк (allowed)
    // С timeWindow=61:                      60 < 61 = true  → ещё активен (blocked)
    // Запрос сделанный 59 сек назад:         59 < 60 = true  → ещё активен (blocked)
    // С timeWindow=59:                       59 < 59 = false → истёк (allowed)

    $user = new User(id: 500, isBot: false, firstName: 'Test');
    $chat = new Chat(id: 1, type: 'private');
    $message = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user, text: 'hi');
    $update = new Update(updateId: 1, message: $message);
    $next = fn (Update $u) => 'ok';

    $setRequests = function (RateLimitTelegramRouteMiddleware $mw, array $timestamps): void {
        $ref = new ReflectionProperty($mw, 'userRequests');
        $ref->setValue($mw, ['500' => $timestamps]);
    };

    $now = time();

    // Запрос ровно 60 сек назад: с дефолтным окном (60) он истёк → следующий разрешён
    $middleware = new RateLimitTelegramRouteMiddleware(maxRequests: 1);
    $setRequests($middleware, [$now - 60]);
    expect($middleware->handle($update, $next))->toBe('ok');

    // Запрос 59 сек назад: с дефолтным окном (60) он ещё активен → следующий заблокирован
    $middleware = new RateLimitTelegramRouteMiddleware(maxRequests: 1);
    $setRequests($middleware, [$now - 59]);
    expect($middleware->handle($update, $next))->toBeNull();
});

it('counts per user separately', function () {
    $user1 = new User(id: 301, isBot: false, firstName: 'A');
    $user2 = new User(id: 302, isBot: false, firstName: 'B');
    $chat = new Chat(id: 1, type: 'private');
    $msg1 = new Message(messageId: 1, date: new DateTimeImmutable, chat: $chat, from: $user1, text: 'a');
    $msg2 = new Message(messageId: 2, date: new DateTimeImmutable, chat: $chat, from: $user2, text: 'b');
    $update1 = new Update(updateId: 1, message: $msg1);
    $update2 = new Update(updateId: 2, message: $msg2);

    $middleware = new RateLimitTelegramRouteMiddleware(maxRequests: 1, timeWindow: 60);
    $next = fn (Update $u) => 'ok';

    expect($middleware->handle($update1, $next))->toBe('ok');
    expect($middleware->handle($update2, $next))->toBe('ok');
    expect($middleware->handle($update1, $next))->toBeNull();
    expect($middleware->handle($update2, $next))->toBeNull();
});
