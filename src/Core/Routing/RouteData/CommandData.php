<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use Phptg\BotApi\Type\Update\Update;

/**
 * @property array<string> $commandParams
 */
final readonly class CommandData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public string $command,
        string $botId,
        public array $commandParams = []
    ) {
        parent::__construct($update, $botId);
    }
}
