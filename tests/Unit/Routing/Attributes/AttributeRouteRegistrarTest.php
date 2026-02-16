<?php

declare(strict_types=1);

use HybridGram\Core\Routing\Attributes\AttributeRouteRegistrar;
use HybridGram\Core\Routing\Attributes\AttributeRouteScanner;
use HybridGram\Core\Routing\ChatType;
use HybridGram\Core\Routing\RouteType;
use HybridGram\Core\Routing\TelegramRouter;

$fixturesDir = __DIR__.'/Fixtures';

it('registers discovered routes into the router', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);
    $discoveredRoutes = $scanner->scan();

    $registrar = new AttributeRouteRegistrar;
    $registrar->register($discoveredRoutes);

    /** @var TelegramRouter $router */
    $router = app(TelegramRouter::class);
    $allRoutes = $router->routes->getRoutes();

    expect($allRoutes)->not->toBeEmpty();
});

it('registers OnCommand route with correct properties', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);
    $discoveredRoutes = $scanner->scan();

    $registrar = new AttributeRouteRegistrar;
    $registrar->register($discoveredRoutes);

    /** @var TelegramRouter $router */
    $router = app(TelegramRouter::class);
    $allRoutes = $router->routes->getRoutes();

    $commandRoutes = $allRoutes[RouteType::COMMAND->name] ?? [];
    $mainBotCommandRoutes = $commandRoutes['main'] ?? [];

    // SampleController has /start command, MultiRouteController has /help for 'secondary' bot
    expect($mainBotCommandRoutes)->toHaveCount(1);

    $startRoute = $mainBotCommandRoutes[0];
    expect($startRoute->type)->toBe(RouteType::COMMAND);
    expect($startRoute->botId)->toBe('main');
    expect($startRoute->pattern)->toBe('/start');
    expect($startRoute->action)->toBe([\Tests\Unit\Routing\Attributes\Fixtures\SampleController::class, 'start']);
    expect($startRoute->chatTypes)->toBe([ChatType::PRIVATE]);
});

it('applies class-level ForBot to all methods', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);
    $discoveredRoutes = $scanner->scan();

    $registrar = new AttributeRouteRegistrar;
    $registrar->register($discoveredRoutes);

    /** @var TelegramRouter $router */
    $router = app(TelegramRouter::class);
    $allRoutes = $router->routes->getRoutes();

    // MultiRouteController is ForBot('secondary')
    $commandRoutes = $allRoutes[RouteType::COMMAND->name] ?? [];
    $secondaryBotRoutes = $commandRoutes['secondary'] ?? [];

    expect($secondaryBotRoutes)->toHaveCount(1);
    expect($secondaryBotRoutes[0]->pattern)->toBe('/help');
});

it('merges class and method level middlewares', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);
    $discoveredRoutes = $scanner->scan();

    $registrar = new AttributeRouteRegistrar;
    $registrar->register($discoveredRoutes);

    /** @var TelegramRouter $router */
    $router = app(TelegramRouter::class);
    $allRoutes = $router->routes->getRoutes();

    // confirm() on SampleController has class-level SampleMiddleware + method-level AnotherMiddleware
    $callbackRoutes = $allRoutes[RouteType::CALLBACK_QUERY->name] ?? [];
    $mainBotCallbackRoutes = $callbackRoutes['main'] ?? [];

    expect($mainBotCallbackRoutes)->toHaveCount(1);

    $confirmRoute = $mainBotCallbackRoutes[0];
    // Should have both middlewares merged
    expect($confirmRoute->middlewares)->toHaveCount(2);
});

it('applies method-level config overriding class-level for same type', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);
    $discoveredRoutes = $scanner->scan();

    $registrar = new AttributeRouteRegistrar;
    $registrar->register($discoveredRoutes);

    /** @var TelegramRouter $router */
    $router = app(TelegramRouter::class);
    $allRoutes = $router->routes->getRoutes();

    // greet() has FromUserState(['greeting']) and ToUserState('welcomed', 3600)
    $textRoutes = $allRoutes[RouteType::TEXT_MESSAGE->name] ?? [];
    $mainBotTextRoutes = $textRoutes['main'] ?? [];

    $greetRoute = null;
    foreach ($mainBotTextRoutes as $route) {
        if ($route->pattern === 'hello*') {
            $greetRoute = $route;
            break;
        }
    }

    expect($greetRoute)->not->toBeNull();
    expect($greetRoute->fromUserState)->toBe(['greeting']);
    expect($greetRoute->toState)->toBe('welcomed');
});

it('applies cache config to route', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);
    $discoveredRoutes = $scanner->scan();

    $registrar = new AttributeRouteRegistrar;
    $registrar->register($discoveredRoutes);

    /** @var TelegramRouter $router */
    $router = app(TelegramRouter::class);
    $allRoutes = $router->routes->getRoutes();

    $textRoutes = $allRoutes[RouteType::TEXT_MESSAGE->name] ?? [];
    $mainBotTextRoutes = $textRoutes['main'] ?? [];

    $cachedRoute = null;
    foreach ($mainBotTextRoutes as $route) {
        if ($route->cacheKey === 'cached_text') {
            $cachedRoute = $route;
            break;
        }
    }

    expect($cachedRoute)->not->toBeNull();
    expect($cachedRoute->cacheTtl)->toBe(60);
    expect($cachedRoute->cacheKey)->toBe('cached_text');
});
