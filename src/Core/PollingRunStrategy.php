<?php

declare(strict_types=1);

namespace HybridGram\Core;

use HybridGram\Core\Config\BotConfig;

final readonly class PollingRunStrategy
{
    public const NONE = 'none';

    public const SINGLE = 'single';

    public const MULTIPLE = 'multiple';

    private function __construct(
        public string $type,
        public ?BotConfig $config = null,
        /** @var BotConfig[] */
        public array $configs = [],
    ) {}

    public static function none(): self
    {
        return new self(self::NONE);
    }

    public static function single(BotConfig $config): self
    {
        return new self(self::SINGLE, $config);
    }

    /**
     * @param  BotConfig[]  $configs
     */
    public static function multiple(array $configs): self
    {
        return new self(self::MULTIPLE, null, $configs);
    }
}
