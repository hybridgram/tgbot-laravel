<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

use HybridGram\Core\MediaGroup\MediaGroupGrouper;
use HybridGram\Models\TelegramUpdate;
use Illuminate\Support\Facades\Cache;
use Phptg\BotApi\Type\Update\Update;

final class WebhookAsyncUpdateMode extends AbstractUpdateMode
{
    public function run(?Update $update = null): void
    {
        if ($update === null) {
            return;
        }

        $this->processUpdate($update);
    }

    /**
     * Process update with database storage and media group handling
     */
    protected function processUpdate(Update $update): void
    {
        $processedUpdate = $this->handleMediaGroup($update, $this->botConfig->botId);

        if ($processedUpdate !== null) {
            $this->processUpdate($processedUpdate);

            if ($processedUpdate->message?->mediaGroupId !== null) {
                MediaGroupGrouper::clearGroupedData($processedUpdate->message->mediaGroupId);
            }
        }
    }

    /**
     * Handle media group updates
     */
    protected function handleMediaGroup(Update $update, string $botId): ?Update
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
