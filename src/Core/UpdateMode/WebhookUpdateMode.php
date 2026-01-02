<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

use Phptg\BotApi\Type\Update\Update;

final class WebhookUpdateMode extends AbstractUpdateMode
{
    public function run(?Update $update = null): void
    {
        $this->processUpdate($update);
    }

    public function serveRoadRunnerServer()
    {

    }

    public function type(): UpdateModeEnum
    {
        return UpdateModeEnum::WEBHOOK;
    }
}