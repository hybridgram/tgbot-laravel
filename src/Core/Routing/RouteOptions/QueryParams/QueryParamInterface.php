<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions\QueryParams;

/**
 * Interface for query parameter validation objects in callback query
 */
interface QueryParamInterface
{
    /**
     * Get parameter key for validation
     */
    public function getKey(): string;

    /**
     * Validate parameter in params array
     *
     * @param  array<string, string>  $params  Parameters from callback query
     * @return bool true if validation passed successfully
     */
    public function matches(array $params): bool;
}
