<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteOptions;

use HybridGram\Telegram\Document\MimeType;

readonly class DocumentOptions
{
    /**
     * @param array<MimeType|string> $mimeTypes
     */
    public function __construct(
        public array $mimeTypes,
    )
    {
    }
}