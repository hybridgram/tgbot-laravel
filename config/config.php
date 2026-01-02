<?php

declare(strict_types=1);

use HybridGram\Core\UpdateMode\UpdateModeEnum;

return [
    'bots' => [
        [
            'token' => env('BOT_TOKEN'),
            'bot_id' => env('BOT_ID', 'main'),
            'update_mode' => UpdateModeEnum::POLLING,
            'routes_file' => base_path(env('TELEGRAM_ROUTES_FILE', 'routes/telegram.php')),
            'polling_limit' => env('TELEGRAM_POLLING_LIMIT', 100),
            'polling_timeout' => env('TELEGRAM_POLLING_TIMEOUT', 0),
            'allowed_updates' => explode(',', env('ALLOWED_TELEGRAM_UPDATES', '')) ,
            'secret_token' => env('TELEGRAM_SECRET_TOKEN'),
            'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
            'webhook_port' => env('TELEGRAM_WEBHOOK_PORT', 9070),
            'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH'),
            'webhook_drop_pending_updates' => env('TELEGRAM_WEBHOOK_DROP_PENDING', false),
            'bot_name' => env('BOT_NAME', 'main'),
        ],
        // Вы можете установить сколько угодно ботов
    ],
    'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org/bot'),
    
    'sending' => [
        // Enable queue-based sending. If false, all requests are sent synchronously with rate limiting.
        'queue_enabled' => env('TELEGRAM_QUEUE_ENABLED', false),

        // Log Telegram API failures (non-2xx / ok=false responses) for outgoing methods.
        'log_failures' => env('TELEGRAM_LOG_FAILURES', true),

        // Include Telegram response body in logs (may contain request-related details).
        'log_response_body' => env('TELEGRAM_LOG_RESPONSE_BODY', true),
        
        // Maximum requests per minute per bot (Telegram limit is ~30 requests/second = 1800/minute)
        'rate_limit_per_minute' => (int) env('TELEGRAM_RATE_LIMIT_PER_MINUTE', 1800),
        
        // Reserve this many requests per minute for HIGH priority (incoming update responses)
        // Low priority requests cannot use these reserved slots
        'reserve_high_per_minute' => (int) env('TELEGRAM_RESERVE_HIGH_PER_MINUTE', 300),
        
        // Maximum wait time in milliseconds for sync mode when rate limit is exceeded
        // Note: rate limiting is applied only in queue mode. This option is used by the worker job sender.
        'sync_max_wait_ms' => (int) env('TELEGRAM_SYNC_MAX_WAIT_MS', 2000),
        
        // Queue names for different priorities
        'queues' => [
            'high' => env('TELEGRAM_QUEUE_HIGH', 'telegram-high'),
            'low' => env('TELEGRAM_QUEUE_LOW', 'telegram-low'),
        ],
    ],
    
    'auth' => [
        // Guard name for Telegram authentication
        'guard' => 'hybridgram',
        
        // User model class (must implement Illuminate\Contracts\Auth\Authenticatable)
        'user_model' => env('TELEGRAM_USER_MODEL', 'App\\Models\\User'),
        
        // Database column name for storing Telegram user ID
        'telegram_id_column' => env('TELEGRAM_ID_COLUMN', 'telegram_id'),
        
        // Automatically create user if not found in database
        'auto_create_user' => env('TELEGRAM_AUTO_CREATE_USER', false),
    ],
];
