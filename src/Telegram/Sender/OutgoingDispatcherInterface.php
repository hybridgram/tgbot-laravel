<?php

declare(strict_types=1);

namespace HybridGram\Telegram\Sender;

use HybridGram\Telegram\Priority;
use Phptg\BotApi\MethodInterface;

interface OutgoingDispatcherInterface
{
    /**
     * Dispatch a Telegram API method for sending.
     *
     * @param string $botId Bot identifier
     * @param MethodInterface $method Telegram API method
     * @param Priority $priority Request priority
     * @return mixed Result from the method call (sync) or void (if queued)
     */
    public function dispatch(string $botId, MethodInterface $method, Priority $priority): mixed;
}

