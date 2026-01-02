<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use HybridGram\Core\UpdateMode\UpdateModeEnum;
use HybridGram\Providers\TelegramServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TelegramServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'ReactBot' => \HybridGram\Facades\ReactBot::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('react_bot', [
            'bots' => [
                [
                    'token' => 'test-token',
                    'bot_id' => 'test-bot',
                    'bot_name' => 'Test Bot',
                    'update_mode' => UpdateModeEnum::POLLING,
                    'routes_file' => 'routes/telegram-webhook.php',
                    'polling_limit' => 100,
                    'polling_timeout' => 0,
                    'allowed_updates' => [],
                    'secret_token' => null,
                    'webhook_url' => null,
                    'webhook_port' => 9070,
                    'webhook_drop_pending_updates' => false,
                    'react_mode' => false,
                    'certificate_path' => null,
                    'ip_address' => null,
                    'drop_pending_updates' => false,
                ],
            ],
        ]);
    }
}
