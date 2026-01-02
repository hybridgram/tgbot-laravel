<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use Illuminate\Support\Facades\App;
use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Core\UpdateHelper;
use Phptg\BotApi\Type\Update\Update;

readonly class CheckStateTelegramRouteMiddleware implements TelegramRouteMiddlewareInterface
{
    /**
     * @param array<string> $requiredStates
     * @param bool $useUserState
     * @param bool $exceptMode Если true, то маршрут будет работать только если состояние НЕ в списке
     */
    public function __construct(
        private array $requiredStates,
        private bool   $useUserState = false,
        private bool   $exceptMode = false
    ) {}

    public function handle(Update $update, callable $next): mixed
    {
        /** @var StateManagerInterface $stateManager */
        $stateManager = App::get(StateManagerInterface::class);
        
        $chat = UpdateHelper::getChatFromUpdate($update);
        $user = UpdateHelper::getUserFromUpdate($update);
        
        if (!$chat) {
            return $next($update);
        }

        if ($this->useUserState && $user) {
            $isInState = $stateManager->isUserInAnyState($chat, $user, $this->requiredStates);
        } else {
            $isInState = $stateManager->isChatInAnyState($chat, $this->requiredStates);
        }

        if ($this->exceptMode) {
            $isInState = !$isInState;
        }

        if (!$isInState) {
            return null;
        }

        return $next($update);
    }
}
