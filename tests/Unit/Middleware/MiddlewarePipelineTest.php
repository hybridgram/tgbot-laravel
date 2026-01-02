<?php

declare(strict_types=1);

use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use HybridGram\Core\Middleware\MiddlewareManager;
use HybridGram\Core\Middleware\MiddlewarePipeline;
use Phptg\BotApi\Type\Update\Update;

it('executes middleware in correct order', function () {
    $manager = new MiddlewareManager();
    $pipeline = new MiddlewarePipeline($manager);
    
    $executionOrder = [];
    
    $middleware1 = new class($executionOrder, 1) implements TelegramRouteMiddlewareInterface {
        public function __construct(private array &$order, private int $id) {}
        
        public function handle(Update $update, callable $next): mixed
        {
            $this->order[] = "before_{$this->id}";
            $result = $next($update);
            $this->order[] = "after_{$this->id}";
            return $result;
        }
    };
    
    $middleware2 = new class($executionOrder, 2) implements TelegramRouteMiddlewareInterface {
        public function __construct(private array &$order, private int $id) {}
        
        public function handle(Update $update, callable $next): mixed
        {
            $this->order[] = "before_{$this->id}";
            $result = $next($update);
            $this->order[] = "after_{$this->id}";
            return $result;
        }
    };
    
    $pipeline->add($middleware1)->add($middleware2);
    
    $update = new Update(1);
    $finalHandler = function (Update $update) use (&$executionOrder) {
        $executionOrder[] = 'final_handler';
        return 'result';
    };
    
    $result = $pipeline->process($update, $finalHandler);
    
    expect($result)->toBe('result');
    expect($executionOrder)->toBe([
        'before_1',
        'before_2', 
        'final_handler',
        'after_2',
        'after_1'
    ]);
});

it('handles empty middleware pipeline', function () {
    $manager = new MiddlewareManager();
    $pipeline = new MiddlewarePipeline($manager);
    
    $update = new Update(1);
    $finalHandler = function (Update $update) {
        return 'result';
    };
    
    $result = $pipeline->process($update, $finalHandler);
    
    expect($result)->toBe('result');
});

it('throws exception for invalid middleware', function () {
    $manager = new MiddlewareManager();
    $pipeline = new MiddlewarePipeline($manager);
    
    expect(fn() => $pipeline->addMany(['invalid']))
        ->toThrow(InvalidArgumentException::class, 'All middlewares must implement MiddlewareInterface');
});
