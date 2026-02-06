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
     * Get current state for chat
     */
    public function getChatState(Chat $chat): ?State;

    /**
     * Get current state for chat and user
     */
    public function getUserState(Chat $chat, User $user): ?State;

    /**
     * Set state for chat
     *
     * @param  Chat  $chat  Chat for which to set the state
     * @param  string  $state  State name
     * @param  int|null  $ttl  TTL in seconds (null — default value, usually 24 hours)
     * @param  array<string, mixed>|null  $data  Optional state data: associative array with arbitrary values (keys — strings). Stored in {@see State} and accessible via {@see State::getKey()}. Pass null if no data needed
     */
    public function setChatState(Chat $chat, string $state, ?int $ttl = null, ?array $data = null): void;

    /**
     * Set state for chat and user
     *
     * @param  Chat  $chat  Chat
     * @param  User  $user  User in chat
     * @param  string  $state  State name
     * @param  int|null  $ttl  TTL in seconds (null — default value, usually 24 hours)
     * @param  array<string, mixed>|null  $data  Optional state data: associative array with arbitrary values (keys — strings). Stored in {@see State} and accessible via {@see State::getKey()}. Pass null if no data needed
     */
    public function setUserState(Chat $chat, User $user, string $state, ?int $ttl = null, ?array $data = null): void;

    /**
     * Clear state for chat
     */
    public function clearChatState(Chat $chat): void;

    /**
     * Clear state for chat and user
     */
    public function clearUserState(Chat $chat, User $user): void;

    /**
     * Check if chat is in the specified state
     */
    public function isChatInState(Chat $chat, string $state): bool;

    /**
     * Check if user in chat is in the specified state
     */
    public function isUserInState(Chat $chat, User $user, string $state): bool;

    /**
     * Check if chat is in one of the specified states
     *
     * @param  array<string>  $states
     */
    public function isChatInAnyState(Chat $chat, array $states): bool;

    /**
     * Check if user in chat is in one of the specified states
     *
     * @param  array<string>  $states
     */
    public function isUserInAnyState(Chat $chat, User $user, array $states): bool;
}
