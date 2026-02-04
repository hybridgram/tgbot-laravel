<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

/**
 * Helper to build Telegram command strings in unified format.
 *
 * Format: /name or /name_arg1_arg2
 * Compatible with CommandData::parseCommandAndArguments (inline args via _).
 */
final readonly class CommandHelper implements \Stringable
{
    private function __construct(
        private string $name,
        private array  $args,
    ) {
        if ($this->name === '') {
            throw new \InvalidArgumentException('Command name must not be empty.');
        }
        if (preg_match('/^[a-z]+$/', $this->name) !== 1) {
            throw new \InvalidArgumentException('Command name must contain only letters a-z.');
        }
    }

    public static function make(string $name, array $args = []): self
    {
        $normalizedName = mb_strtolower($name);
        $safeArgs = array_map(static function (mixed $value): string {
            if (is_array($value) || is_object($value)) {
                throw new \InvalidArgumentException('Command argument must be scalar or null.');
            }
            $s = $value === null ? '' : (string) $value;
            $s = mb_strtolower($s);
            if ($s === '') {
                throw new \InvalidArgumentException('Command argument must not be empty.');
            }
            if (preg_match('/^[a-z0-9]+$/i', $s) !== 1) {
                throw new \InvalidArgumentException('Command argument must contain only letters a-z and digits 0-9.');
            }

            return $s;
        }, array_values($args));

        return new self($normalizedName, $safeArgs);
    }

    public function __toString(): string
    {
        $base = '/'.$this->name;
        if ($this->args === []) {
            return $base;
        }
        return $base.'_'.implode('_', $this->args);
    }
}
