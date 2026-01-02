<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use Illuminate\Support\Facades\App;
use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use HybridGram\Core\State\StateManager;
use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Core\UpdateHelper;
use Phptg\BotApi\Type\Update\Update;

final readonly class SetStateTelegramRouteMiddleware implements TelegramRouteMiddlewareInterface
{
    public function __construct(
        private string $newState,
        private ?int   $ttl = null,
        private bool   $useUserState = false,
        private mixed  $data = null
    ) {}

    public function handle(Update $update, callable $next): mixed
    {
        $result = $next($update);
        /** @var StateManager $stateManager */
        $stateManager = App::get(StateManagerInterface::class);
        
        $chat = UpdateHelper::getChatFromUpdate($update);
        $user = UpdateHelper::getUserFromUpdate($update);
        
        if (!$chat) {
            return $result;
        }

        if ($this->useUserState && $user) {
            $stateManager->setUserState($chat, $user, $this->newState, $this->ttl, $this->data);
        } else {
            $stateManager->setChatState($chat, $this->newState, $this->ttl, $this->data);
        }

        return $result;
    }
}
