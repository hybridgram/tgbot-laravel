<?php

declare(strict_types=1);

namespace HybridGram\Core\Middleware;


final class MiddlewareManager
{
    /** @var array<TelegramRouteMiddlewareInterface>  */
    private array $globalMiddlewares = [];

    /**
     * Регистрация глобальных middleware
     */
    public function registerGlobalMiddleware(TelegramRouteMiddlewareInterface ...$middleware): MiddlewareManager
    {
        $this->globalMiddlewares = $middleware;
        return $this;
    }

    public function getGlobalMiddlewares(): array
    {
        return $this->globalMiddlewares;
    }
}
