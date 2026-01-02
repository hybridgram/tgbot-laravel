<?php

declare(strict_types=1);

namespace HybridGram\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Phptg\BotApi\Type\Update\Update;

final class TelegramUserProvider implements UserProvider
{
    private string $model;
    private string $telegramIdColumn;
    private bool $autoCreateUser;
    private ?\Closure $userCreationCallback = null;

    public function __construct(
        string $model,
        string $telegramIdColumn,
        bool $autoCreateUser,
        ?\Closure $userCreationCallback = null
    ) {
        $this->model = $model;
        $this->telegramIdColumn = $telegramIdColumn;
        $this->autoCreateUser = $autoCreateUser;
    }



    /**
     * Retrieve a user by the given credentials.
     *
     * @param array<string, mixed> $credentials
     * @param Update|null $update
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials, ?Update $update = null): ?Authenticatable
    {
        if (!isset($credentials[$this->telegramIdColumn])) {
            return null;
        }

        $model = $this->createModel();
        $telegramId = $credentials[$this->telegramIdColumn];

        $user = $model->newQuery()
            ->where($this->telegramIdColumn, $telegramId)
            ->first();

        if ($user === null && $this->autoCreateUser) {
            $user = $this->createUser($telegramId, $update);
        }

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param array<string, mixed> $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials[$this->telegramIdColumn])) {
            return false;
        }

        $telegramId = $credentials[$this->telegramIdColumn];
        $userTelegramId = $this->getUserTelegramId($user);

        return $userTelegramId !== null && (string) $userTelegramId === (string) $telegramId;
    }

    /**
     * Create a new user instance.
     *
     * @return Authenticatable
     */
    private function createModel(): Authenticatable
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Create a new user with the given Telegram ID.
     *
     * @param int|string $telegramId
     * @param Update|null $update
     * @return Authenticatable|null
     */
    private function createUser($telegramId, ?Update $update = null): ?Authenticatable
    {
        // Если есть callback для создания пользователя, используем его
        if ($this->userCreationCallback !== null && $update !== null) {
            try {
                $user = ($this->userCreationCallback)($update, $telegramId);
                if ($user instanceof Authenticatable) {
                    return $user;
                }
            } catch (\Throwable $e) {
                logger()->error('Failed to create user via callback', [
                    'telegram_id' => $telegramId,
                    'error' => $e->getMessage(),
                ]);
                // Fallback to default creation if callback fails
            }
        }

        // Default creation logic
        $model = $this->createModel();

        try {
            $user = $model->newQuery()->create([
                $this->telegramIdColumn => $telegramId,
            ]);

            return $user;
        } catch (\Throwable $e) {
            // Log error but don't throw - allow execution to continue
            logger()->error('Failed to create user', [
                'telegram_id' => $telegramId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Telegram ID from user model.
     *
     * @param Authenticatable $user
     * @return int|string|null
     */
    private function getUserTelegramId(Authenticatable $user): int|string|null
    {
        if (method_exists($user, 'getAttribute')) {
            return $user->getAttribute($this->telegramIdColumn);
        }

        if (property_exists($user, $this->telegramIdColumn)) {
            return $user->{$this->telegramIdColumn};
        }

        return null;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false)
    {}

    public function retrieveById($identifier): ?Authenticatable
    {
        return null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {}
}

