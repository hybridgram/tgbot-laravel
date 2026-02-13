<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use HybridGram\Core\Routing\TelegramRouteBuilder;

final class AttributeRouteRegistrar
{
    /**
     * @param  list<DiscoveredRoute>  $discoveredRoutes
     */
    public function register(array $discoveredRoutes): void
    {
        foreach ($discoveredRoutes as $discoveredRoute) {
            $this->registerRoute($discoveredRoute);
        }
    }

    private function registerRoute(DiscoveredRoute $discoveredRoute): void
    {
        $builder = new TelegramRouteBuilder;

        $mergedConfigs = $this->mergeConfigs(
            $discoveredRoute->classConfigAttributes,
            $discoveredRoute->methodConfigAttributes,
        );

        foreach ($mergedConfigs as $config) {
            $config->applyToBuilder($builder);
        }

        $action = [$discoveredRoute->className, $discoveredRoute->methodName];

        $discoveredRoute->routeAttribute->registerRoute($builder, $action);
    }

    /**
     * Merge class-level and method-level config attributes.
     *
     * TgMiddlewares: MERGE (class + method combined).
     * All others: method-level REPLACES class-level for the same attribute type.
     *
     * @param  list<TelegramRouteConfigAttribute>  $classConfigs
     * @param  list<TelegramRouteConfigAttribute>  $methodConfigs
     * @return list<TelegramRouteConfigAttribute>
     */
    private function mergeConfigs(array $classConfigs, array $methodConfigs): array
    {
        $methodConfigTypes = [];
        foreach ($methodConfigs as $config) {
            $methodConfigTypes[$config::class] = true;
        }

        $merged = [];

        foreach ($classConfigs as $config) {
            if ($config instanceof TgMiddlewares) {
                // Middlewares always apply — they merge additively via builder
                $merged[] = $config;
            } elseif (! isset($methodConfigTypes[$config::class])) {
                // Class-level config only applies if method doesn't override it
                $merged[] = $config;
            }
        }

        foreach ($methodConfigs as $config) {
            $merged[] = $config;
        }

        return $merged;
    }
}
