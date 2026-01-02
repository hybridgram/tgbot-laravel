<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Sender;

use HybridGram\Telegram\Priority;
use Phptg\BotApi\MethodInterface;

/**
 * Direct synchronous sender without any rate limiting.
 *
 * Used when queue-based sending is disabled.
 */
final class DirectOutgoingDispatcher implements OutgoingDispatcherInterface
{
    public function dispatch(string $botId, MethodInterface $method, Priority $priority): mixed
    {
        $client = new \Phptg\BotApi\TelegramBotApi($this->getTokenForBot($botId));

        return $client->call($method);
    }

    private function getTokenForBot(string $botId): string
    {
        $config = \HybridGram\Core\Config\BotConfig::getBotConfig($botId);
        if ($config === null) {
            throw new \RuntimeException("Bot config not found for botId '{$botId}'.");
        }

        return $config->token;
    }
}


