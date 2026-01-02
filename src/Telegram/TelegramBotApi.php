<?php

declare(strict_types=1);

namespace HybridGram\Telegram;

use HybridGram\Core\Config\BotConfig;
use HybridGram\Exceptions\Telegram\TelegramRequestError;
use HybridGram\Telegram\Sender\OutgoingDispatcherInterface;
use Illuminate\Support\Facades\App;
use Psr\Log\LoggerInterface;
use SensitiveParameter;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\MethodInterface;
use Phptg\BotApi\TelegramBotApi as VjikTelegramBotApi;
use Phptg\BotApi\Method\SendMessage;
use Phptg\BotApi\Method\AnswerCallbackQuery;
use Phptg\BotApi\Method\Inline\AnswerInlineQuery;
use Phptg\BotApi\Method\Payment\AnswerPreCheckoutQuery;
use Phptg\BotApi\Method\Payment\AnswerShippingQuery;
use Phptg\BotApi\Method\Inline\AnswerWebAppQuery;
use Phptg\BotApi\Method\UpdatingMessage\EditMessageText;
use Phptg\BotApi\Method\UpdatingMessage\EditMessageCaption;
use Phptg\BotApi\Method\UpdatingMessage\EditMessageReplyMarkup;
use Phptg\BotApi\Method\UpdatingMessage\DeleteMessage;
use Phptg\BotApi\Method\ForwardMessage;
use Phptg\BotApi\Type\LinkPreviewOptions;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\MessageEntity;
use Phptg\BotApi\Type\ReplyParameters;
use Phptg\BotApi\Type\Inline\InlineQueryResult;
use Phptg\BotApi\Type\Inline\InlineQueryResultsButton;
use Phptg\BotApi\Type\Payment\ShippingOption;
use Phptg\BotApi\Type\Inline\SentWebAppMessage;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Phptg\BotApi\Type\ReplyKeyboardMarkup;
use Phptg\BotApi\Type\ReplyKeyboardRemove;
use Phptg\BotApi\Type\ForceReply;
use Phptg\BotApi\Type\SuggestedPostParameters;

/**
 * Enhanced Telegram Bot API client with queue support and rate limiting.
 * 
 * This is a wrapper around Phptg\BotApi\TelegramBotApi that adds:
 * - Outgoing request dispatcher (sync/queue based on configuration)
 * - Rate limiting per bot
 * - Priority support for requests
 * - Automatic routing: outgoing methods go through dispatcher, service methods go directly
 * 
 * @api
 */
final class TelegramBotApi
{
    private readonly VjikTelegramBotApi $originalClient;
    private ?OutgoingDispatcherInterface $dispatcher = null;
    private ?string $botId = null;
    private Priority $priority = Priority::HIGH;

    /**
     * Methods that should always go directly (bypass dispatcher)
     * These are informational/getting/setting webhook methods, not outgoing messages
     */
    private const array SERVICE_METHODS = [
        'getUpdates',
        'setWebhook',
        'deleteWebhook',
        'getWebhookInfo',
        'getMe',
        'getFile',
        'getChat',
        'getChatMember',
        'getChatAdministrators',
        'getChatMemberCount',
        'getChatMenuButton',
        'getMyCommands',
        'getMyDefaultAdministratorRights',
        'getMyDescription',
        'getMyName',
        'getMyShortDescription',
        'getMyStarBalance',
        'getBusinessConnection',
        'getBusinessAccountGifts',
        'getBusinessAccountStarBalance',
        'getCustomEmojiStickers',
        'getForumTopicIconStickers',
        'getGameHighScores',
        'getUserProfilePhotos',
        'getUserChatBoosts',
        'getAvailableGifts',
        'getStickerSet',
        'getStarTransactions',
        'downloadFile',
        'downloadFileTo',
        'makeFileUrl',
        'logOut',
        'close',
    ];

    public function __construct(
        #[SensitiveParameter]
        private readonly string $token,
        private readonly string $baseUrl = 'https://api.telegram.org',
        ?VjikTelegramBotApi $originalClient = null,
        ?OutgoingDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->originalClient = $originalClient ?? new VjikTelegramBotApi($token, $baseUrl, null, $logger);
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set priority for subsequent requests.
     * Returns new instance with updated priority.
     */
    public function withPriority(Priority $priority): self
    {
        $new = clone $this;
        $new->priority = $priority;
        return $new;
    }

    /**
     * Set bot ID for dispatcher context.
     * Internal use only.
     * 
     * @internal
     */
    public function withBotId(string $botId): self
    {
        $new = clone $this;
        $new->botId = $botId;
        return $new;
    }

    /**
     * @see https://core.telegram.org/bots/api#making-requests
     *
     * @psalm-template TValue
     * @psalm-param MethodInterface<TValue> $method
     * @psalm-return TValue|FailResult
     */
    public function call(MethodInterface $method): mixed
    {
        if ($this->isServiceMethod($method)) {
            return $this->originalClient->call($method);
        }

        $result = null;
        if ($this->dispatcher !== null && $this->botId !== null) {
            $priority = $this->priority;
            $result = $this->dispatcher->dispatch($this->botId, $method, $priority);
        } else {
            $result = $this->originalClient->call($method);
        }

        if ($result instanceof FailResult) {
            $this->reportOutgoingFailResult($result);

            throw TelegramRequestError::fromFailResult($result);
        }

        return $result;
    }

    private function reportOutgoingFailResult(FailResult $failResult): void
    {
        if (! (bool) config('hybridgram.sending.log_failures', true)) {
            return;
        }

        $context = [
            'bot_id' => $this->botId,
            'method' => $failResult->method->getApiMethod(),
            'error_code' => $failResult->errorCode,
            'description' => $failResult->description,
            'status_code' => $failResult->response->statusCode ?? null,
        ];

        if ((bool) config('hybridgram.sending.log_response_body', true)) {
            $context['telegram_response'] = $failResult->response->body ?? null;
        }

        logger()->error('Telegram outgoing request failed', $context);
    }

    private function isServiceMethod(MethodInterface $method): bool
    {
        $apiMethod = $method->getApiMethod();
        return in_array($apiMethod, self::SERVICE_METHODS, true);
    }

    /**
     * Make a file URL on Telegram servers.
     * Delegates to original client.
     * 
     * @see VjikTelegramBotApi::makeFileUrl()
     */
    public function makeFileUrl(string|\Phptg\BotApi\Type\File $file): string
    {
        return $this->originalClient->makeFileUrl($file);
    }

    /**
     * Downloads a file from the Telegram servers and returns its content.
     * Delegates to original client.
     * 
     * @see VjikTelegramBotApi::downloadFile()
     */
    public function downloadFile(string|\Phptg\BotApi\Type\File $file): string
    {
        return $this->originalClient->downloadFile($file);
    }

    /**
     * Downloads a file from the Telegram servers and saves it to a file.
     * Delegates to original client.
     * 
     * @see VjikTelegramBotApi::downloadFileTo()
     */
    public function downloadFileTo(string|\Phptg\BotApi\Type\File $file, string $savePath): void
    {
        $this->originalClient->downloadFileTo($file, $savePath);
    }

    /**
     * @see https://core.telegram.org/bots/api#sendmessage
     * 
     * Overridden to route through dispatcher.
     */
    public function sendMessage(
        int|string $chatId,
        string $text,
        ?string $businessConnectionId = null,
        ?int $messageThreadId = null,
        ?string $parseMode = null,
        ?array $entities = null,
        ?LinkPreviewOptions $linkPreviewOptions = null,
        ?bool $disableNotification = null,
        ?bool $protectContent = null,
        ?string $messageEffectId = null,
        ?ReplyParameters $replyParameters = null,
        InlineKeyboardMarkup|ReplyKeyboardMarkup|ReplyKeyboardRemove|ForceReply|null $replyMarkup = null,
        ?bool $allowPaidBroadcast = null,
        ?int $directMessagesTopicId = null,
        ?SuggestedPostParameters $suggestedPostParameters = null,
    ): FailResult|Message {
        return $this->call(
            new SendMessage(
                $chatId,
                $text,
                $businessConnectionId,
                $messageThreadId,
                $parseMode,
                $entities,
                $linkPreviewOptions,
                $disableNotification,
                $protectContent,
                $messageEffectId,
                $replyParameters,
                $replyMarkup,
                $allowPaidBroadcast,
                $directMessagesTopicId,
                $suggestedPostParameters,
            ),
        );
    }

    /**
     * @see https://core.telegram.org/bots/api#answercallbackquery
     * 
     * Overridden to route through dispatcher.
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        ?bool $showAlert = null,
        ?string $url = null,
        ?int $cacheTime = null,
    ): FailResult|true {
        return $this->call(
            new AnswerCallbackQuery($callbackQueryId, $text, $showAlert, $url, $cacheTime),
        );
    }

    /**
     * @see https://core.telegram.org/bots/api#answerinlinequery
     * 
     * Overridden to route through dispatcher.
     */
    public function answerInlineQuery(
        string $inlineQueryId,
        array $results,
        ?int $cacheTime = null,
        ?bool $isPersonal = null,
        ?string $nextOffset = null,
        ?InlineQueryResultsButton $button = null,
    ): FailResult|true {
        return $this->call(
            new AnswerInlineQuery($inlineQueryId, $results, $cacheTime, $isPersonal, $nextOffset, $button),
        );
    }

    /**
     * @see https://core.telegram.org/bots/api#editmessagetext
     * 
     * Overridden to route through dispatcher.
     */
    public function editMessageText(
        string $text,
        ?string $businessConnectionId = null,
        int|string|null $chatId = null,
        ?int $messageId = null,
        ?string $inlineMessageId = null,
        ?string $parseMode = null,
        ?array $entities = null,
        ?LinkPreviewOptions $linkPreviewOptions = null,
        ?InlineKeyboardMarkup $replyMarkup = null,
    ): FailResult|Message|true {
        return $this->call(
            new EditMessageText(
                $text,
                $businessConnectionId,
                $chatId,
                $messageId,
                $inlineMessageId,
                $parseMode,
                $entities,
                $linkPreviewOptions,
                $replyMarkup,
            ),
        );
    }

    /**
     * @see https://core.telegram.org/bots/api#deletemessage
     * 
     * Overridden to route through dispatcher.
     */
    public function deleteMessage(int|string $chatId, int $messageId): FailResult|true
    {
        return $this->call(new DeleteMessage($chatId, $messageId));
    }

    /**
     * Delegate all other convenience methods to original client.
     * Note: These will bypass dispatcher. For dispatcher support, use call() directly.
     * 
     * @see self::call() for dispatcher-enabled method calls
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (method_exists($this->originalClient, $method)) {
            $result = $this->originalClient->$method(...$arguments);

            // Even if the method bypasses dispatcher (by design), do not silently ignore Telegram failures.
            if ($result instanceof FailResult && !in_array($method, self::SERVICE_METHODS, true)) {
                $this->reportOutgoingFailResult($result);
                throw TelegramRequestError::fromFailResult($result);
            }

            return $result;
        }

        throw new \BadMethodCallException("Method '{$method}' does not exist on " . self::class);
    }
}

