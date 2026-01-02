<?php

declare(strict_types=1);

namespace HybridGram\Models;

use Illuminate\Database\Eloquent\Model;
use Phptg\BotApi\Type\Update\Update;

final class TelegramUpdate extends Model
{
    protected $fillable = [
        'update_id',
        'bot_id',
        'update_data',
        'mode',
        'processed_at',
    ];

    protected $casts = [
        'update_data' => 'array',
        'processed_at' => 'datetime',
    ];

    public static function storeUpdate(Update $update, string $botId, string $mode): self
    {
        return self::firstOrCreate(
            [
                'update_id' => $update->updateId,
                'bot_id' => $botId,
            ],
            [
                'update_data' => $update,
                'mode' => $mode,
            ]
        );
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
