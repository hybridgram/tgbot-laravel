<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions;

use HybridGram\Telegram\Poll\PollType;

readonly class PollOptions
{
    public function __construct(
        public ?bool $isAnonymous = null,
        public ?PollType $pollType = null,
    )
    {
    }
}