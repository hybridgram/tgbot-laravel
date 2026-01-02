<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

enum UpdateModeEnum: string
{
    case POLLING = 'polling';
    case WEBHOOK = 'webhook';
    case QUEUE = 'queue';
}
