<?php

declare(strict_types=1);

use HybridGram\Core\Routing\Attributes\AttributeRouteScanner;
use HybridGram\Core\Routing\Attributes\DiscoveredRoute;
use HybridGram\Core\Routing\Attributes\ForBot;
use HybridGram\Core\Routing\Attributes\OnCallbackQuery;
use HybridGram\Core\Routing\Attributes\OnCommand;
use HybridGram\Core\Routing\Attributes\OnTextMessage;

$fixturesDir = __DIR__.'/Fixtures';

it('discovers routes from annotated controller classes', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);

    $routes = $scanner->scan();

    // SampleController has 4 methods with route attributes, MultiRouteController has 5
    // NoAttributeController has 0
    expect($routes)->toHaveCount(9);
    expect($routes[0])->toBeInstanceOf(DiscoveredRoute::class);
});

it('correctly identifies route attributes on SampleController', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);

    $routes = $scanner->scan();

    $sampleRoutes = array_values(array_filter(
        $routes,
        fn (DiscoveredRoute $r) => $r->className === Tests\Unit\Routing\Attributes\Fixtures\SampleController::class,
    ));

    expect($sampleRoutes)->toHaveCount(4);

    // First method: start with OnCommand
    $startRoute = $sampleRoutes[0];
    expect($startRoute->methodName)->toBe('start');
    expect($startRoute->routeAttribute)->toBeInstanceOf(OnCommand::class);
    expect($startRoute->routeAttribute->pattern)->toBe('/start');

    // Second method: greet with OnTextMessage
    $greetRoute = $sampleRoutes[1];
    expect($greetRoute->methodName)->toBe('greet');
    expect($greetRoute->routeAttribute)->toBeInstanceOf(OnTextMessage::class);
    expect($greetRoute->routeAttribute->pattern)->toBe('hello*');

    // Third method: confirm with OnCallbackQuery
    $confirmRoute = $sampleRoutes[2];
    expect($confirmRoute->methodName)->toBe('confirm');
    expect($confirmRoute->routeAttribute)->toBeInstanceOf(OnCallbackQuery::class);
    expect($confirmRoute->routeAttribute->pattern)->toBe('confirm_*');
});

it('collects class-level config attributes', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);

    $routes = $scanner->scan();

    $sampleRoutes = array_values(array_filter(
        $routes,
        fn (DiscoveredRoute $r) => $r->className === Tests\Unit\Routing\Attributes\Fixtures\SampleController::class,
    ));

    // Every route from SampleController should have ForBot, ChatTypes, TgMiddlewares at class level
    foreach ($sampleRoutes as $route) {
        expect($route->classConfigAttributes)->toHaveCount(3);
        expect($route->classConfigAttributes[0])->toBeInstanceOf(ForBot::class);
        expect($route->classConfigAttributes[0]->botId)->toBe('main');
    }
});

it('collects method-level config attributes', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);

    $routes = $scanner->scan();

    $sampleRoutes = array_values(array_filter(
        $routes,
        fn (DiscoveredRoute $r) => $r->className === Tests\Unit\Routing\Attributes\Fixtures\SampleController::class,
    ));

    // start() has no method-level config attributes
    expect($sampleRoutes[0]->methodConfigAttributes)->toHaveCount(0);

    // greet() has FromUserState + ToUserState
    expect($sampleRoutes[1]->methodConfigAttributes)->toHaveCount(2);

    // confirm() has SendAction + TgMiddlewares
    expect($sampleRoutes[2]->methodConfigAttributes)->toHaveCount(2);
});

it('ignores classes without route attributes', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir]);

    $routes = $scanner->scan();

    $noAttrRoutes = array_filter(
        $routes,
        fn (DiscoveredRoute $r) => $r->className === Tests\Unit\Routing\Attributes\Fixtures\NoAttributeController::class,
    );

    expect($noAttrRoutes)->toBeEmpty();
});

it('excludes directories from scanning', function () use ($fixturesDir) {
    $scanner = new AttributeRouteScanner([$fixturesDir], [$fixturesDir]);

    $routes = $scanner->scan();

    expect($routes)->toBeEmpty();
});

it('handles non-existent directories gracefully', function () {
    $scanner = new AttributeRouteScanner(['/non/existent/directory']);

    $routes = $scanner->scan();

    expect($routes)->toBeEmpty();
});
