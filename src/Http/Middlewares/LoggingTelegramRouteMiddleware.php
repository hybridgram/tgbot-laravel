<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use Phptg\BotApi\Type\Update\Update;

class LoggingTelegramRouteMiddleware implements TelegramRouteMiddlewareInterface
{
    public function handle(Update $update, callable $next): mixed
    {
        logger()->info('Processing update', [
            'update_id' => $update->updateId,
            'type' => $this->getUpdateType($update)
        ]);
        
        $startTime = microtime(true);
        
        $result = $next($update);
        
        $executionTime = microtime(true) - $startTime;

        logger()->info('Update processed', [
            'update_id' => $update->updateId,
            'execution_time' => round($executionTime * 1000, 2) . 'ms'
        ]);
        
        return $result;
    }
    
    private function getUpdateType(Update $update): string
    {
        if ($update->message) return 'message';
        if ($update->editedMessage) return 'edited_message';
        if ($update->channelPost) return 'channel_post';
        if ($update->editedChannelPost) return 'edited_channel_post';
        if ($update->inlineQuery) return 'inline_query';
        if ($update->chosenInlineResult) return 'chosen_inline_result';
        if ($update->callbackQuery) return 'callback_query';
        if ($update->shippingQuery) return 'shipping_query';
        if ($update->preCheckoutQuery) return 'pre_checkout_query';
        if ($update->poll) return 'poll';
        if ($update->pollAnswer) return 'poll_answer';
        if ($update->myChatMember) return 'my_chat_member';
        if ($update->chatMember) return 'chat_member';
        if ($update->chatJoinRequest) return 'chat_join_request';
        if ($update->chatBoost) return 'chat_boost';
        if ($update->removedChatBoost) return 'removed_chat_boost';
        
        return 'unknown';
    }
}
