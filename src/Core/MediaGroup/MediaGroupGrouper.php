<?php

declare(strict_types=1);

namespace HybridGram\Core\MediaGroup;

use Illuminate\Support\Facades\Cache;
use Phptg\BotApi\Type\Update\Update;

final class MediaGroupGrouper
{
    public static function groupUpdates(array $updates): array
    {
        if (empty($updates)) {
            return [];
        }

        $groups = [];
        $ungrouped = [];

        foreach ($updates as $update) {
            if ($update->message?->mediaGroupId !== null) {
                $mediaGroupId = $update->message->mediaGroupId;
                if (!isset($groups[$mediaGroupId])) {
                    $groups[$mediaGroupId] = [];
                }
                $groups[$mediaGroupId][] = $update;
            } else {
                $ungrouped[] = $update;
            }
        }

        $result = [];

        foreach ($groups as $mediaGroupId => $groupUpdates) {
            if (count($groupUpdates) > 1) {
                $firstUpdate = $groupUpdates[0];

                if ($firstUpdate->message?->photo !== null && !empty($firstUpdate->message->photo)) {
                    $extractedPhotos = self::extractPhotosFromGroup($groupUpdates);
                    if (!empty($extractedPhotos)) {
                        $cacheKey = "media_group_items_{$mediaGroupId}";
                        Cache::put($cacheKey, $extractedPhotos, 10);
                    }
                }

                $groupedUpdate = self::createGroupedUpdate($groupUpdates);
                $result[] = $groupedUpdate;
            } else {
                $ungrouped[] = $groupUpdates[0];
            }
        }

        $result = array_merge($result, $ungrouped);

        return $result;
    }

    private static function createGroupedUpdate(array $updates): Update
    {
        if (empty($updates)) {
            throw new \InvalidArgumentException('Cannot create grouped update from empty array');
        }

        return $updates[0];
    }

    private static function extractPhotos(Update $update): ?array
    {
        if ($update->message?->photo === null || empty($update->message->photo)) {
            return null;
        }

        return $update->message->photo;
    }

    public static function extractPhotosFromGroup(array $updates): array
    {
        $photos = [];
        foreach ($updates as $update) {
            $photo = self::extractPhotos($update);
            if ($photo !== null) {
                $photos[] = $photo;
            }
        }
        return $photos;
    }

    public static function getGroupedPhotos(string $mediaGroupId): ?array
    {
        $cacheKey = "media_group_items_{$mediaGroupId}";
        return Cache::get($cacheKey);
    }

    public static function clearGroupedData(string $mediaGroupId): int
    {
        $cacheKey = "media_group_items_{$mediaGroupId}";
        $items = self::getGroupedPhotos($mediaGroupId) ?? [];
        Cache::forget($cacheKey);
        return count($items);
    }
}

