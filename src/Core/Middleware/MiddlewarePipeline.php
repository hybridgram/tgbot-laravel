<?php

declare(strict_types=1);

namespace HybridGram\Core\Middleware;

use Phptg\BotApi\Type\Update\Update;

class MiddlewarePipeline
{
    private array $middlewares = [];
    public function __construct()
    {
    }

    public function add(TelegramRouteMiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function addMany(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof TelegramRouteMiddlewareInterface) {
                throw new \InvalidArgumentException('All middlewares must implement MiddlewareInterface');
            }
            $this->middlewares[] = $middleware;
        }
        return $this;
    }

    public function process(Update $update, callable $finalHandler): mixed
    {
        if (empty($this->middlewares)) {
            return $finalHandler($update);
        }
        
        $pipeline = $this->createPipeline($this->middlewares, $finalHandler);
        
        return $pipeline($update);
    }

    private function createPipeline(array $middlewares, callable $finalHandler): callable
    {
        $pipeline = $finalHandler;

        for ($i = count($middlewares) - 1; $i >= 0; $i--) {
            $middleware = $middlewares[$i];
            $pipeline = function (Update $update) use ($middleware, $pipeline) {
                return $middleware->handle($update, $pipeline);
            };
        }
        
        return $pipeline;
    }
}
