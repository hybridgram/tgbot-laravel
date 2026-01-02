<?php
/**
 * Используется в случае стандартной HTTP реализации бота
 * Можно указать свой контроллер
 */
\Illuminate\Support\Facades\Route::post('/telegram/bot/webhook/{botId}',
    [\HybridGram\Http\Controllers\WebhookController::class, 'handle']
)->name('telegram.bot.webhook'); // todo мидлварь и из конфига контроллер подставлять