<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use Illuminate\Support\Facades\App;
use HybridGram\Core\UpdateHelper;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Phptg\BotApi\Type\Update\Update;

/**
 * @property array $routes An array of the routes keyed by method.
 * @property array $allRoutes A flattened array of all the routes
 */
final class RouteCollection
{
    public function __construct(
        protected array $routes = [],
    ) {}

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function add(TelegramRoute $needleRoute): TelegramRoute
    {
        $this->routes[$needleRoute->type->name][$needleRoute->botId][] = $needleRoute;

        return $needleRoute;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public function findRoute(
        Update $update,
        ?string $botId,
        RouteStates $states,
    ): TelegramRoute {

        $routeType = UpdateHelper::mapToRouteType($update);
        $chatType = UpdateHelper::getChatType($update);

        $matchRoutes = $this->routes[$routeType->name][$botId] ?? $this->routes[$routeType->name]['*'] ?? [];

        if (! empty($matchRoutes)) {
            $matchRoutes = $this->applyFilters($matchRoutes, $states, $chatType);

            if (! empty($matchRoutes)) {
                foreach ($matchRoutes as $matchRoute) {
                    $result = $matchRoute->matches($update);
                    if ($result) {
                        $matchRoute->data = $result;

                        return $matchRoute;
                    }
                }
            }
        }

        // find routed with ANY route type
        $withAnyRoutes = $this->routes[RouteType::ANY->name][$botId] ?? $this->routes[RouteType::ANY->name]['*'] ?? [];

        if (! empty($withAnyRoutes)) {
            $withAnyRoutes = $this->applyFilters($withAnyRoutes, $states, $chatType);

            if (! empty($withAnyRoutes)) {
                foreach ($withAnyRoutes as $matchRoute) {
                    $result = $matchRoute->matches($update);
                    if ($result) {
                        $matchRoute->data = $result;

                        return $matchRoute;
                    }
                }
            }
        }

        // fallback if not found route
        $fallbackRoute = $this->routes[RouteType::FALLBACK->name][$botId] ?? (App::get(TelegramRouter::class)->fallbackRoute($update, $botId));

        if (empty($fallbackRoute)) {
            throw new RouteNotFoundException("Route with type: $routeType->name for $botId not found");
        }

        return $fallbackRoute;

    }

    /**
     * @param  TelegramRoute[]  $prematchRoutes
     * @return TelegramRoute[]
     */
    protected function applyFilters(
        array $prematchRoutes,
        RouteStates $states,
        ?ChatType $chatType = null
    ): array {
        return array_filter($prematchRoutes, function (TelegramRoute $route) use ($states, $chatType) {
            if ($chatType !== $route->chatType) {
                return false;
            }

            if ($route->exceptUserState !== null) {
                if ($states->userState !== null && in_array($states->userState->getName(), $route->exceptUserState, true)) {
                    return false;
                }
            }

            if ($route->exceptChatState !== null) {
                if ($states->chatState !== null && in_array($states->chatState->getName(), $route->exceptChatState, true)) {
                    return false;
                }
            }

            if ($route->fromUserState !== null) {
                if ($states->userState === null) {
                    return false;
                }
                return in_array($states->userState->getName(), $route->fromUserState, true);
            }

            if ($route->fromChatState !== null) {
                if ($states->chatState === null) {
                    return false;
                }
                return in_array($states->chatState->getName(), $route->fromChatState, true);
            }

            return true;
        });
    }
}
