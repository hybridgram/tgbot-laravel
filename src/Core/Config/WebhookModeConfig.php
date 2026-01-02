<?php

declare(strict_types=1);

namespace HybridGram\Core\Config;

use SensitiveParameter;

final class WebhookModeConfig
{
    public function __construct(
        public ?string $url = null,
        public ?int $port = null,
        public ?string $certificatePath = null,
        public ?string $ipAddress = null,
        public array $allowedUpdates = [],
        public bool $dropPendingUpdates = false,
        #[SensitiveParameter]
        public ?string $secretToken = null
    ) {}
}
