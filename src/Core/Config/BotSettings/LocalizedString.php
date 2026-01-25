<?php

declare(strict_types=1);

namespace HybridGram\Core\Config\BotSettings;

/**
 * Represents a string value that can be localized for different languages.
 */
final class LocalizedString
{
    /**
     * @var array<string|null, string> Map of language code => value. null key means default.
     */
    private array $values = [];

    /**
     * Add a localized value.
     *
     * @param string $value The string value
     * @param string|null $languageCode ISO 639-1 language code (e.g., 'en', 'ru'). Null for default.
     */
    public function add(string $value, ?string $languageCode = null): self
    {
        $this->values[$languageCode] = $value;

        return $this;
    }

    /**
     * Get the value for a specific language code.
     */
    public function get(?string $languageCode = null): ?string
    {
        return $this->values[$languageCode] ?? null;
    }

    /**
     * Get all values with their language codes.
     *
     * @return array<string|null, string>
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Check if any values are set.
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * Check if a specific language code has a value.
     */
    public function has(?string $languageCode = null): bool
    {
        return isset($this->values[$languageCode]);
    }
}
