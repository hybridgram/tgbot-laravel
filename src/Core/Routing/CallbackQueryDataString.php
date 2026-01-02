<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

/**
 * Helper to build and parse Telegram `callback_data` in unified format.
 *
 * Format:
 *   <action>|<key>=<value>&<key2>=<value2>
 *
 * - action and each key/value are RFC3986 encoded (rawurlencode)
 * - total byte length must be 1..64 (Telegram limit)
 */
final class CallbackQueryDataString implements \Stringable
{
    private const int MIN_BYTES = 1;
    private const int MAX_BYTES = 64;

    /** @var array<string, string> */
    private array $params = [];

    private function __construct(
        private readonly string $action,
    ) {
        if ($this->action === '') {
            throw new \InvalidArgumentException('CallbackQuery action must not be empty.');
        }
    }

    public static function make(string $action): self
    {
        return new self($action);
    }

    public function add(string $name, mixed $value): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('CallbackQuery param name must not be empty.');
        }

        if (is_array($value) || is_object($value)) {
            throw new \InvalidArgumentException('CallbackQuery param value must be scalar or null.');
        }

        $this->params[$name] = match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };

        return $this;
    }

    public function toString(): string
    {
        $encodedAction = rawurlencode($this->action);

        if ($this->params === []) {
            self::assertByteLength($encodedAction);
            return $encodedAction;
        }

        $pairs = [];
        foreach ($this->params as $k => $v) {
            $pairs[] = rawurlencode($k) . '=' . rawurlencode($v);
        }

        $result = $encodedAction . '|' . implode('&', $pairs);
        self::assertByteLength($result);

        return $result;
    }

    public static function parse(string $data): ParsedCallbackQueryData
    {
        self::assertByteLength($data);

        [$actionRaw, $query] = array_pad(explode('|', $data, 2), 2, null);
        $actionRaw ??= '';

        $action = rawurldecode($actionRaw);
        if ($action === '') {
            throw new \InvalidArgumentException('CallbackQuery action is missing.');
        }

        $params = [];
        if ($query !== null && $query !== '') {
            foreach (explode('&', $query) as $pair) {
                if ($pair === '') {
                    continue;
                }
                [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
                $key = rawurldecode($k);
                if ($key === '') {
                    throw new \InvalidArgumentException('CallbackQuery param name is empty.');
                }
                $params[$key] = rawurldecode($v);
            }
        }

        return new ParsedCallbackQueryData($action, $params);
    }

    private static function assertByteLength(string $data): void
    {
        $bytes = strlen($data);
        if ($bytes < self::MIN_BYTES || $bytes > self::MAX_BYTES) {
            throw new \InvalidArgumentException(
                sprintf('Telegram callback_data must be %d..%d bytes, got %d bytes.', self::MIN_BYTES, self::MAX_BYTES, $bytes)
            );
        }
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}


