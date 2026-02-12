<?php

declare(strict_types=1);

namespace HybridGram\Core\State;

/**
 * @property array<string, mixed>|null $data
 */
final readonly class State
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        private string $name,
        private ?array $data = null
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    public function hasData(): bool
    {
        return $this->data !== null;
    }

    public function getKey(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}
