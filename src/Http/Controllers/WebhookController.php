<?php

declare(strict_types=1);

namespace HybridGram\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\UpdateMode\UpdateModeEnum;
use HybridGram\Core\UpdateMode\WebhookUpdateMode;
use Phptg\BotApi\Type\Update\Update;

final class WebhookController
{
    public function handle(Request $request, BotConfig $botConfig): Response
    {
        $update = Update::fromJson($request->getContent());

        $updateMode = new WebhookUpdateMode($botConfig);
        $updateMode->run($update);

        return response('OK', 200);
    }
}
