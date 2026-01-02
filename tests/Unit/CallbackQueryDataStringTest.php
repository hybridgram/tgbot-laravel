<?php

declare(strict_types=1);

use HybridGram\Core\Routing\CallbackQueryDataString;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\Routing\TelegramRoute;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

it('builds unified callback_data and parses it back into assoc params', function () {
    $data = CallbackQueryDataString::make('do')
        ->add('id', 10)
        ->add('flag', true)
        ->add('empty', null)
        ->toString();

    $parsed = CallbackQueryDataString::parse($data);

    expect($parsed->action)->toBe('do');
    expect($parsed->params)->toBe([
        'id' => '10',
        'flag' => '1',
        'empty' => '',
    ]);
});

it('enforces telegram callback_data 1..64 bytes limit', function () {
    CallbackQueryDataString::make('a')
        ->add('x', str_repeat('b', 200))
        ->toString();
})->throws(\InvalidArgumentException::class);


it('TelegramRoute::matchesCallbackQuery returns null on invalid callback_data', function () {
    $from = new User(id: 1, isBot: false, firstName: 'Test');
    $query = new CallbackQuery(id: 'cq1', from: $from, chatInstance: 'ci1', data: '');
    $update = new Update(updateId: 1, callbackQuery: $query);

    $route = new TelegramRoute(type: RouteType::CALLBACK_QUERY, pattern: '*');
    expect($route->matchesCallbackQuery($update))->toBeNull();
});


