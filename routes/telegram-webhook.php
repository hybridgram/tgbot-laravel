<?php

declare(strict_types=1);
/**
 * Used in case of standard HTTP bot implementation
 * You can specify your own controller
 */
Illuminate\Support\Facades\Route::post('/telegram/bot/webhook/{botId}',
    [HybridGram\Http\Controllers\WebhookController::class, 'handle']
)->name('telegram.bot.webhook'); // todo middleware and controller from config
