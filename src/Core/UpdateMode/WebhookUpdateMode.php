<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

use Phptg\BotApi\Type\Update\Update;

final class WebhookUpdateMode extends AbstractUpdateMode
{
    public function run(?Update $update = null): void
    {
        if ($update === null) {
            return;
        }

        $this->processUpdate($update);
    }
}