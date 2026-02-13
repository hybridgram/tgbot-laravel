# HybridGram
Laravel toolkit for fast Telegram bots creation with Go-powered webhook updating.

[![Latest Stable Version](https://poser.pugx.org/phptg/bot-api/v)](https://packagist.org/packages/phptg/bot-api)
[![Total Downloads](https://poser.pugx.org/phptg/bot-api/downloads)](https://packagist.org/packages/phptg/bot-api)
[![Build status](https://github.com/phptg/bot-api/actions/workflows/build.yml/badge.svg)](https://github.com/phptg/bot-api/actions/workflows/build.yml)
[![Coverage Status](https://coveralls.io/repos/github/phptg/bot-api/badge.svg)](https://coveralls.io/github/phptg/bot-api)
[![Mutation score](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fphptg%2Fbot-api%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/phptg/bot-api/master)
[![Static analysis](https://github.com/phptg/bot-api/actions/workflows/psalm.yml/badge.svg?branch=master)](https://github.com/phptg/bot-api/actions/workflows/psalm.yml?query=branch%3Amaster)


## Quick start

```bash
composer require hybridgram/tgbot-laravel
php artisan vendor:publish --provider="HybridGram\Providers\TelegramServiceProvider"
```

Set `BOT_TOKEN` (and optional `BOT_ID`, `BOT_NAME`) in `.env`. Routing defaults to `routes/telegram.php`.

## Minimal working example 

`routes/telegram.php`

```php
<?php

use HybridGram\Facades\TelegramRouter;
use HybridGram\Core\Routing\RouteData\TextMessageData;
use HybridGram\Core\Routing\RouteData\PollData;
use HybridGram\Telegram\Poll\PollType;
use HybridGram\Telegram\TelegramBotApi;

TelegramRouter::group(['for_bot' => 'main'], function (\HybridGram\Core\Routing\TelegramRouteBuilder $builder) {
    $builder->onTextMessage(function (TextMessageData $message) {
        $api = app(TelegramBotApi::class);
        $api->sendMessage($message->getChatId(), "Echo: {$message->text}");
    });
    // you can use any of route
//   $builder->onPoll(function (PollData $poll) {
//        $api = app(TelegramBotApi::class);
//        $api->sendMessage(
//            $poll->getChatId(),
//            "Poll received ({$poll->poll->type}) with " . count($poll->poll->options) . " options"
//        );
//    }, pollType: PollType::REGULAR);
});
```

Run polling in dev:

```bash
php artisan hybridgram:polling --hot-reload
```

## Go module (go-proxy) and async updates for production

The bundled `./vendor/bin/tgook` script downloads the `go-proxy` binary (from `hybridgram/go-proxy`) and runs it with your `.env` to stream updates via a high-performance Go worker. Use it when you need long-lived, asynchronous update handling without blocking PHP:

```bash
# install & run go-proxy with your .env
php ./vendor/bin/tgook
```

## Key features

- Typed router covering Telegram updates (`onMessage`, `onPoll`, callbacks, media, etc.).
- Hot-reload polling for fast local development.
- Queue-aware outbound sending with rate limiting and HIGH/LOW priorities.
- Artisan helpers: set/delete webhook, list routes, configure bot settings.

## Docs

- Supported update handlers: `telegram-update-types.md`
