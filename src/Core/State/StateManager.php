<?php

declare(strict_types=1);

namespace HybridGram\Core\State;

use Illuminate\Support\Facades\Cache;
use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\User;

class StateManager implements StateManagerInterface
{
    private const string CACHE_PREFIX_CHAT = 'telegram_state_chat_';
    private const string CACHE_PREFIX_USER = 'telegram_state_user_';
    private const int CACHE_TTL = 86400; // 24 часа

    public function getChatState(Chat $chat): ?State
    {
        $stored = Cache::get($this->getChatKey($chat));
        return $this->deserializeState($stored);
    }

    public function getUserState(Chat $chat, User $user): ?State
    {
        $stored = Cache::get($this->getUserKey($chat, $user));
        return $this->deserializeState($stored);
    }

    public function setChatState(Chat $chat, string $state, ?int $ttl = null, ?array $data = null): void
    {
        if (is_null($ttl)) {
            $ttl = self::CACHE_TTL;
        }

        $stateObj = new State($state, $data);
        $serialized = $this->serializeState($stateObj);
        Cache::put($this->getChatKey($chat), $serialized, $ttl);
    }

    public function setUserState(Chat $chat, User $user, string $state, ?int $ttl = null, ?array $data = null): void
    {
        if (is_null($ttl)) {
            $ttl = self::CACHE_TTL;
        }

        $stateObj = new State($state, $data);
        $serialized = $this->serializeState($stateObj);
        Cache::put($this->getUserKey($chat, $user), $serialized, $ttl);
    }

    public function clearChatState(Chat $chat): void
    {
        Cache::forget($this->getChatKey($chat));
    }

    public function clearUserState(Chat $chat, User $user): void
    {
        Cache::forget($this->getUserKey($chat, $user));
    }

    public function isChatInState(Chat $chat, string $state): bool
    {
        $currentState = $this->getChatState($chat);
        return $currentState !== null && $currentState->getName() === $state;
    }

    public function isUserInState(Chat $chat, User $user, string $state): bool
    {
        $currentState = $this->getUserState($chat, $user);
        return $currentState !== null && $currentState->getName() === $state;
    }

    public function isChatInAnyState(Chat $chat, array $states): bool
    {
        $currentState = $this->getChatState($chat);
        if ($currentState === null) {
            return false;
        }
        return in_array($currentState->getName(), $states, true);
    }

    public function isUserInAnyState(Chat $chat, User $user, array $states): bool
    {
        $currentState = $this->getUserState($chat, $user);
        if ($currentState === null) {
            return false;
        }
        return in_array($currentState->getName(), $states, true);
    }

    private function getChatKey(Chat $chat): string
    {
        return self::CACHE_PREFIX_CHAT . $chat->id;
    }

    private function getUserKey(Chat $chat, User $user): string
    {
        return self::CACHE_PREFIX_USER . $chat->id . '_' . $user->id;
    }

    private function serializeState(State $state): array
    {
        return [
            'name' => $state->getName(),
            'data' => $state->getData(),
        ];
    }

    private function deserializeState(mixed $stored): ?State
    {
        if (is_array($stored) && isset($stored['name'])) {
            return new State($stored['name'], $stored['data'] ?? null);
        }

        return null;
    }
}
