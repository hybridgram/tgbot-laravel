<?php

declare(strict_types=1);

namespace HybridGram\Core\State;

use Phptg\BotApi\Type\Chat;
use Phptg\BotApi\Type\User;

/**
 * @see StateManager
 */
interface StateManagerInterface
{
    /**
     * Получить текущий стейт для чата
     */
    public function getChatState(Chat $chat): ?State;

    /**
     * Получить текущий стейт для чата и пользователя
     */
    public function getUserState(Chat $chat, User $user): ?State;

    /**
     * Установить стейт для чата
     * @param mixed $data Опциональные данные стейта
     */
    public function setChatState(Chat $chat, string $state, ?int $ttl = null, ?array $data = null): void;

    /**
     * Установить стейт для чата и пользователя
     * @param mixed $data Опциональные данные стейта
     */
    public function setUserState(Chat $chat, User $user, string $state, ?int $ttl = null, ?array $data = null): void;

    /**
     * Очистить стейт для чата
     */
    public function clearChatState(Chat $chat): void;

    /**
     * Очистить стейт для чата и пользователя
     */
    public function clearUserState(Chat $chat, User $user): void;

    /**
     * Проверить, находится ли чат в указанном стейте
     */
    public function isChatInState(Chat $chat, string $state): bool;

    /**
     * Проверить, находится ли пользователь в чате в указанном стейте
     */
    public function isUserInState(Chat $chat, User $user, string $state): bool;

    /**
     * Проверить, находится ли чат в одном из указанных стейтов
     * @param array<string> $states
     */
    public function isChatInAnyState(Chat $chat, array $states): bool;

    /**
     * Проверить, находится ли пользователь в чате в одном из указанных стейтов
     * @param array<string> $states
     */
    public function isUserInAnyState(Chat $chat, User $user, array $states): bool;
}
