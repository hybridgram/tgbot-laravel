<?php

declare(strict_types=1);

use HybridGram\Http\Middlewares\SetLocaleTelegramRouteMiddleware;
use Illuminate\Support\Facades\App;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

beforeEach(function () {
    $this->chat = new Chat(id: 123, type: 'private');
});

it('sets locale from user language code', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test', languageCode: 'ru');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware();
    $next = fn($update) => 'processed';

    $result = $middleware->handle($update, $next);

    expect($result)->toBe('processed');
    expect(App::getLocale())->toBe('ru');
});

it('normalizes hyphenated locale codes', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test', languageCode: 'pt-br');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware();
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('pt_br');
});

it('uses fallback locale when user has no language code', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware(fallbackLocale: 'en');
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('en');
});

it('uses fallback locale when user locale is not supported', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test', languageCode: 'ja');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware(
        supportedLocales: ['en', 'ru', 'uk'],
        fallbackLocale: 'en'
    );
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('en');
});

it('sets locale when it is in supported locales list', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test', languageCode: 'uk');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware(
        supportedLocales: ['en', 'ru', 'uk'],
        fallbackLocale: 'en'
    );
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('uk');
});

it('matches base locale when full locale is not in supported list', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test', languageCode: 'en-gb');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware(
        supportedLocales: ['en', 'ru'],
        fallbackLocale: 'ru'
    );
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('en_gb');
});

it('does not change locale when no user in update and no fallback', function () {
    App::setLocale('de');

    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        text: 'test message'
    );
    $update = new Update(updateId: 1, message: $message);

    $middleware = new SetLocaleTelegramRouteMiddleware();
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('de');
});

it('works with callback query updates', function () {
    $user = new User(id: 456, isBot: false, firstName: 'Test', languageCode: 'fr');
    $message = new Message(
        messageId: 1,
        date: new \DateTimeImmutable(),
        chat: $this->chat,
        from: $user,
        text: 'test message'
    );
    $callbackQuery = new \Phptg\BotApi\Type\CallbackQuery(
        id: 'callback_123',
        from: $user,
        chatInstance: 'instance',
        message: $message
    );
    $update = new Update(updateId: 1, callbackQuery: $callbackQuery);

    $middleware = new SetLocaleTelegramRouteMiddleware();
    $next = fn($update) => 'processed';

    $middleware->handle($update, $next);

    expect(App::getLocale())->toBe('fr');
});
