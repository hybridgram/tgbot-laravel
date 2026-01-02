<?php

declare(strict_types=1);

namespace HybridGram\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\MediaGroup\MediaGroupGrouper;
use HybridGram\Core\Routing\TelegramRouter;
use HybridGram\Core\UpdateMode\WebhookUpdateMode;
use HybridGram\Models\TelegramUpdate;
use Phptg\BotApi\Type\Update\Update;

final class WebhookController
{
    private ?WebhookUpdateMode $updateMode = null;

    public function handle(Request $request, BotConfig $botConfig): Response
    {
        $update = Update::fromJson($request->getContent());

        $telegramUpdate = TelegramUpdate::storeUpdate($update, $botConfig->botId, $botConfig->getUpdateMode()->value);
        if ($telegramUpdate->processed_at) {
            return response('OK', 200);
        }

        $processedUpdate = $this->handleMediaGroup($update, $botConfig->botId);

        if ($processedUpdate !== null) {
            $this->updateMode = new WebhookUpdateMode($botConfig);
            $this->updateMode->run($processedUpdate);

            if ($processedUpdate->message?->mediaGroupId !== null) {
                MediaGroupGrouper::clearGroupedData($processedUpdate->message->mediaGroupId);
            }
            
            $telegramUpdate->markAsProcessed();
        }

        return response('OK', 200);
    }

    private function handleMediaGroup(Update $update, string $botId): ?Update
    {
        if ($update->message?->mediaGroupId !== null) {
            $mediaGroupId = $update->message->mediaGroupId;
            $cacheKey = "media_group_{$mediaGroupId}";

            $groupUpdates = Cache::get($cacheKey, []);
            $groupUpdates[] = $update;

            if (count($groupUpdates) > 1) {
                $groupedUpdates = MediaGroupGrouper::groupUpdates($groupUpdates);

                Cache::forget($cacheKey);

                return $groupedUpdates[0] ?? $update;
            } else {
                Cache::put($cacheKey, $groupUpdates, 10);
                return null;
            }
        }

        return $update;
    }
}
