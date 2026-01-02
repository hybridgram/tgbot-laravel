<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions\QueryParams;

/**
 * Проверяет существование ключа в query параметрах
 */
final readonly class Exist implements QueryParamInterface
{
    public function __construct(
        private string $key,
    ) {
        if ($this->key === '') {
            throw new \InvalidArgumentException('Query param key must not be empty.');
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function matches(array $params): bool
    {
        return isset($params[$this->key]);
    }
}

