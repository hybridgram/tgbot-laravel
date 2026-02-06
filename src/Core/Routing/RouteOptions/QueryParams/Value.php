<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions\QueryParams;

/**
 * Checks value of key in query parameters
 * Supports validation by string, number, or via callable
 */
final readonly class Value implements QueryParamInterface
{
    /**
     * @param  string  $key  Parameter key
     * @param  string|\Closure|int  $expectedValue  Expected value or callable for validation
     */
    public function __construct(
        private string $key,
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
        if (! isset($params[$this->key])) {
            return false;
        }

        $actualValue = $params[$this->key];

        // If expected value is callable, invoke it
        if (is_callable($this->expectedValue)) {
            return (bool) call_user_func($this->expectedValue, $actualValue);
        }

        // Compare as strings
        return (string) $this->expectedValue === $actualValue;
    }
}
