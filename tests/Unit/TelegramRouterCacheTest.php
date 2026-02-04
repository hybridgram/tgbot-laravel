<?php

declare(strict_types=1);

use HybridGram\Core\Routing\TelegramRouter;
use Illuminate\Support\Facades\Cache;

it('can cache routes with closures using SerializableClosure', function () {
    // Clear cache first
    Cache::flush();

    $router = new TelegramRouter;

    $this->app->instance(TelegramRouter::class, $router);

    // Add routes with string actions (action, botId, pattern)
    $router->onCommand('TestController@start', 'test_bot', 'start');
    $router->onMessage('TestController@hello', 'test_bot', 'hello');

    // Add routes with closures (should now be cached with SerializableClosure)
    $router->onCommand(function () {
        return 'This is a closure';
    }, 'test_bot', 'closure');

    $router->onMessage(function () {
        return 'This is also a closure';
    }, 'test_bot', 'closure_pattern');

    // Cache routes - should not throw serialization error
    expect(fn () => $router->cacheRoutes())->not->toThrow(Exception::class);

    // Verify cache was created
    $cacheKey = 'telegram_routes_collection';
    expect(Cache::has($cacheKey))->toBeTrue();

    // Load from cache
    $loaded = $router->loadRoutesFromCache();
    expect($loaded)->toBeTrue();

    // Verify all routes were cached (including closures)
    $routes = $router->routes->getRoutes();

    expect($routes)->toHaveKey('COMMAND');
    expect($routes)->toHaveKey('TEXT_MESSAGE');

    // Should have routes for 'test_bot'
    expect($routes['COMMAND'])->toHaveKey('test_bot');
    expect($routes['TEXT_MESSAGE'])->toHaveKey('test_bot');

    // Should have all routes including closures
    expect(count($routes['COMMAND']['test_bot']))->toBe(2); // start + closure
    expect(count($routes['TEXT_MESSAGE']['test_bot']))->toBe(2); // hello + closure_pattern

    // Verify closures are still callable after restoration
    $closureCommand = $routes['COMMAND']['test_bot'][1];
    expect($closureCommand->action)->toBeInstanceOf(Closure::class);

    $closureMessage = $routes['TEXT_MESSAGE']['test_bot'][1];
    expect($closureMessage->action)->toBeInstanceOf(Closure::class);
});

it('can clear routes cache', function () {
    $router = new TelegramRouter;

    $this->app->instance(TelegramRouter::class, $router);

    $router->onCommand('TestController@start', 'test_bot', 'start');

    // Cache routes
    $router->cacheRoutes();

    // Verify cache exists
    $cacheKey = 'telegram_routes_collection';
    expect(Cache::has($cacheKey))->toBeTrue();

    // Clear cache
    $router->clearRoutesCache();

    // Verify cache is cleared
    expect(Cache::has($cacheKey))->toBeFalse();
});
