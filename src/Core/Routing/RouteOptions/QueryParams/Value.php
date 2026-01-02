<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions\QueryParams;

/**
 * Проверяет значение ключа в query параметрах
 * Поддерживает проверку по строке, числу или через callable
 */
final readonly class Value implements QueryParamInterface
{
    /**
     * @param string $key Ключ параметра
     * @param string|\Closure|int $expectedValue Ожидаемое значение или callable для проверки
     */
    public function __construct(
        private string              $key,
        private string|\Closure|int $expectedValue,
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
        if (!isset($params[$this->key])) {
            return false;
        }

        $actualValue = $params[$this->key];

        // Если ожидаемое значение - callable, вызываем его
        if (is_callable($this->expectedValue)) {
            return (bool) call_user_func($this->expectedValue, $actualValue);
        }

        // Сравниваем как строки
        return (string) $this->expectedValue === $actualValue;
    }
}

