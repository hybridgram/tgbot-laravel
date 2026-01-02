<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions\QueryParams;

/**
 * Интерфейс для объектов проверки query параметров в callback query
 */
interface QueryParamInterface
{
    /**
     * Получить ключ параметра для проверки
     */
    public function getKey(): string;

    /**
     * Проверить параметр в массиве params
     * 
     * @param array<string, string> $params Параметры из callback query
     * @return bool true если проверка прошла успешно
     */
    public function matches(array $params): bool;
}

