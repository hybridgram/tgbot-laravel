<?php

declare(strict_types=1);

namespace HybridGram\Facades;

use HybridGram\Core\Routing\TelegramRouteBuilder;
use HybridGram\Core\Routing\TelegramRouter as TelegramRouterService;
use HybridGram\Telegram\ChatMember\ChatMemberStatus;
use HybridGram\Telegram\Document\MimeType;
use HybridGram\Telegram\Poll\PollType;
use Illuminate\Support\Facades\Facade;

/**
 * @method static TelegramRouteBuilder forBot(string $botId)
 * @method static void group(array $attributes, null|\Closure|string|array<string> $callback) - receive TelegramRouteBuilder as callback parameter
 * @method static void onAnimation(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onAudio(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onSticker(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onVideoNote(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onVoice(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onStory(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPaidMedia(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onCommand(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null, ?\Closure $commandParamOptions = null)
 * @method static void onDocument(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null, ?array<MimeType|string> $documentOptions = null)
 * @method static void onLocation(array|string|\Closure $action, string $botId = '*')
 * @method static void onTextMessage(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onBusinessMessageText(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPhoto(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPhotoMediaGroup(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPoll(array|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null)
 * @method static void onPollAnswered(array|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null)
 * @method static void onPollClosed(array|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null,  ?bool $isAnonymous = null, ?PollType $pollType = null)
 * @method static void onVenue(array|string|\Closure $action, string $botId = '*')
 * @method static void onContact(array|string|\Closure $action, string $botId = '*')
 * @method static void onChecklist(array|string|\Closure $action, string $botId = '*')
 * @method static void onDice(array|string|\Closure $action, string $botId = '*')
 * @method static void onGame(array|string|\Closure $action, string $botId = '*')
 * @method static void onInvoice(array|string|\Closure $action, string $botId = '*')
 * @method static void onSuccessfulPayment(array|string|\Closure $action, string $botId = '*')
 * @method static void onPassportData(array|string|\Closure $action, string $botId = '*')
 * @method static void onBusinessConnection(array|string|\Closure $action, string $botId = '*')
 * @method static void onReply(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onExternalReply(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onQuote(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onReplyToStory(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onNewChatTitle(array|string|\Closure $action, string $botId = '*')
 * @method static void onNewChatPhoto(array|string|\Closure $action, string $botId = '*')
 * @method static void onDeleteChatPhoto(array|string|\Closure $action, string $botId = '*')
 * @method static void onMessageAutoDeleteTimerChanged(array|string|\Closure $action, string $botId = '*')
 * @method static void onPinnedMessage(array|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicEvent(array|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicCreated(array|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicEdited(array|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicClosed(array|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicReopened(array|string|\Closure $action, string $botId = '*')
 * @method static void onGeneralForumTopicEvent(array|string|\Closure $action, string $botId = '*')
 * @method static void onBoostAdded(array|string|\Closure $action, string $botId = '*')
 * @method static void onCallbackQuery(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null, ?array<string, string|null>|array<int, \HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface> $queryParams = null)
 * @method static void onInlineQuery(array|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onMyChatMember(array|string|\Closure $action, string $botId = '*', ?bool $isBot = null, ?array<ChatMemberStatus> $allowedStatuses = null)
 * @method static void onChatMember(array|string|\Closure $action, string $botId = '*', ?bool $isBot = null, ?array<ChatMemberStatus> $allowedStatuses = null)
 * @method static void onAny(array|string|\Closure $action, string $botId = '*')
 * @method static void onFallback(array|string|\Closure $action, string $botId = '*')
 *
 * @see TelegramRouterService
 */
final class TelegramRouter extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return TelegramRouterService::class;
    }
}
