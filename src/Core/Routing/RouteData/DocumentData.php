<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\TelegramRoute;
use HybridGram\Telegram\Document\MimeType;
use Phptg\BotApi\Type\Document;
use Phptg\BotApi\Type\Update\Update;

final readonly class DocumentData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public Document $document,
        string $botId,
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?DocumentData
    {
        if (! isset($update->message->document)) {
            return null;
        }

        if ($route->documentOptions !== null) {
            $mimeType = $update->message->document->mimeType;
            $matches = false;

            foreach ($route->documentOptions as $allowedType) {
                if ($allowedType instanceof MimeType) {
                    if ($allowedType->value === $mimeType) {
                        $matches = true;
                        break;
                    }
                } elseif (is_string($allowedType)) {
                    if ($allowedType === $mimeType) {
                        $matches = true;
                        break;
                    }
                }
            }

            if (! $matches) {
                return null;
            }
        }

        if ($route->pattern === null || $route->pattern === '*') {
            return new DocumentData($update, $update->message->document, $route->botId);
        }

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($route->pattern, $update)) {
                return null;
            }

            return new DocumentData($update, $update->message->document, $route->botId);
        }

        if (is_string($route->pattern) && $route->pattern !== $update->message->caption) {
            return null;
        }

        return new DocumentData($update, $update->message->document, $route->botId);
    }
}
