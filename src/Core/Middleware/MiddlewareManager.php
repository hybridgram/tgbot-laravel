<?php

declare(strict_types=1);

namespace HybridGram\Core\Middleware;

final class MiddlewareManager
{
    /** @var array<TelegramRouteMiddlewareInterface> */
    private array $globalMiddlewares = [];

    /**
     * Registration of global middlewares
     */
    public function registerGlobalMiddleware(TelegramRouteMiddlewareInterface ...$middleware): MiddlewareManager
    {
        $this->globalMiddlewares = $middleware;

        return $this;
    }

    /**
     * @return array<TelegramRouteMiddlewareInterface>
     */
    public function getGlobalMiddlewares(): array
    {
        return $this->globalMiddlewares;
    }
}
