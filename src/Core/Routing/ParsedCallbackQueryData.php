<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

/**
 * DTO for parsed callback query data.
 */
final readonly class ParsedCallbackQueryData
{
    /**
     * @param string $action The action name
     * @param array<string, string> $params The parsed parameters
     */
    public function __construct(
        public string $action,
        public array $params = [],
    ) {
        if ($this->action === '') {
            throw new \InvalidArgumentException('CallbackQuery action must not be empty.');
        }
    }
}

