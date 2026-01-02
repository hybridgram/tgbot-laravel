<?php

declare(strict_types=1);

namespace HybridGram\Auth;

use HybridGram\Core\UpdateHelper;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Phptg\BotApi\Type\Update\Update;

final class TelegramGuard implements Guard
{
    private ?Authenticatable $user = null;
    private ?Update $update = null;
    private TelegramUserProvider $provider;

    public function __construct(TelegramUserProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->update === null) {
            return null;
        }

        $telegramUser = UpdateHelper::getUserFromUpdate($this->update);
        if ($telegramUser === null) {
            return null;
        }

        $telegramId = $telegramUser->id;
        if ($telegramId === null) {
            return null;
        }

        $authConfig = config('hybridgram.auth', []);
        $telegramIdColumn = $authConfig['telegram_id_column'] ?? 'telegram_id';

        $credentials = [
            $telegramIdColumn => $telegramId,
        ];

        $this->user = $this->provider->retrieveByCredentials($credentials, $this->update);

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id(): int|string|null
    {
        $user = $this->user();

        return $user?->getAuthIdentifier();
    }

    /**
     * Validate a user's credentials.
     *
     * @param array<string, mixed> $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Set the current user.
     *
     * @param Authenticatable|null $user
     * @return void
     */
    public function setUser(?Authenticatable $user): void
    {
        $this->user = $user;
    }

    /**
     * Set the current Update object.
     * This should be called before user() to authenticate based on the Update.
     *
     * @param Update $update
     * @return void
     */
    public function setUpdate(Update $update): void
    {
        $this->update = $update;
        $this->user = null;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }
}

