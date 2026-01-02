<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use HybridGram\Core\Routing\TelegramRouter;
use HybridGram\Facades\TelegramRouter as TelegramRouterFacade;

it('can cache routes with closures using SerializableClosure', function () {
    // Clear cache first
    Cache::flush();
    
    $router = new TelegramRouter();
    
    // Add routes with string actions
    $router->onCommand('test_bot', 'start', 'TestController@start');
    $router->onMessage('test_bot', 'hello', 'TestController@hello');
    
    // Add routes with closures (should now be cached with SerializableClosure)
    $router->onCommand('test_bot', 'closure', function() {
        return 'This is a closure';
    });
    
    $router->onMessage('test_bot', 'closure_pattern', function() {
        return 'This is also a closure';
    });
    
    // Cache routes - should not throw serialization error
    expect(fn() => $router->cacheRoutes())->not->toThrow(Exception::class);
    
    // Verify cache was created
    $cacheKey = 'telegram_routes_collection';
    expect(Cache::has($cacheKey))->toBeTrue();
    
    // Load from cache
    $loaded = $router->loadRoutesFromCache();
    expect($loaded)->toBeTrue();
    
    // Verify all routes were cached (including closures)
    $routes = $router->routes->getRoutes();
    expect($routes)->toHaveKey('COMMAND');
    expect($routes)->toHaveKey('MESSAGE');
    
    // Should have routes for 'test_bot'
    expect($routes['COMMAND'])->toHaveKey('test_bot');
    expect($routes['MESSAGE'])->toHaveKey('test_bot');
    
    // Should have all routes including closures
    expect(count($routes['COMMAND']['test_bot']))->toBe(2); // start + closure
    expect(count($routes['MESSAGE']['test_bot']))->toBe(2); // hello + closure_pattern
    
    // Verify closures are still callable after restoration
    $closureCommand = $routes['COMMAND']['test_bot'][1];
    expect($closureCommand->action)->toBeInstanceOf(\Closure::class);
    
    $closureMessage = $routes['MESSAGE']['test_bot'][1];
    expect($closureMessage->action)->toBeInstanceOf(\Closure::class);
});

it('can clear routes cache', function () {
    $router = new TelegramRouter();
    
    // Add some routes
    $router->onCommand('test_bot', 'start', 'TestController@start');
    
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
