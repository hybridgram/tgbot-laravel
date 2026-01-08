<?php

namespace HybridGram\Telegram;

use Attribute;
use HybridGram\Core\Config\BotConfig;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class TelegramBotApiAttribute implements ContextualAttribute
{
    /**
     * Create a new attribute instance.
     */
    public function __construct(
        public ?string $botId = null,
        public ?Priority $priority = null
    ) {
    }

    /**
     * Resolve the TelegramBotApi instance.
     *
     * @param  self  $attribute
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return TelegramBotApi
     */
    public static function resolve(self $attribute, Container $container): TelegramBotApi
    {
        if ($attribute->botId !== null) {
            $botId = $attribute->botId;
        } elseif ($container->bound('telegram.botId')) {
            $botId = $container->make('telegram.botId');
        } else {
            throw new \RuntimeException(
                'botId is required for TelegramBotApi. ' .
                'Either specify it in the attribute: #[TelegramBotApi(botId: "your-bot-id")] ' .
                'or ensure it is available in the container context.'
            );
        }

        $telegramBotApi = $container->make(TelegramBotApi::class, ['botId' => $botId]);

        if ($attribute->priority !== null) {
            $priority = $attribute->priority;
        } elseif ($container->bound('telegram.priority')) {
            $priority = $container->make('telegram.priority');
        } else {
            $priority = Priority::HIGH;
        }

        return $telegramBotApi->withPriority($priority);
    }
}