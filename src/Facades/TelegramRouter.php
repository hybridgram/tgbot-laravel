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
 * @method static void group(array<string, string|string[]> $attributes, null|\Closure|string|array<string> $callback) - receive TelegramRouteBuilder as callback parameter
 * @method static void onAnimation(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onAudio(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onSticker(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onVideoNote(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onVoice(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onStory(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPaidMedia(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onCommand(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null, ?\Closure $commandParamOptions = null)
 * @method static void onDocument(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null, ?array<MimeType|string> $documentOptions = null)
 * @method static void onLocation(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onTextMessage(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onBusinessMessageText(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPhoto(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPhotoMediaGroup(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onPoll(string[]|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null)
 * @method static void onPollAnswered(string[]|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null)
 * @method static void onPollClosed(string[]|string|\Closure $action, string $botId = '*', ?\Closure $pattern = null,  ?bool $isAnonymous = null, ?PollType $pollType = null)
 * @method static void onVenue(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onContact(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onChecklist(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onDice(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onGame(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onInvoice(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onSuccessfulPayment(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onPassportData(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onBusinessConnection(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onReply(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onExternalReply(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onQuote(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onReplyToStory(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onNewChatTitle(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onNewChatPhoto(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onDeleteChatPhoto(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onMessageAutoDeleteTimerChanged(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onPinnedMessage(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicCreated(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicEdited(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicClosed(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onForumTopicReopened(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onGeneralForumTopicEvent(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onBoostAdded(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onCallbackQuery(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null, array<string, string|null>|array<int, \HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface>|null $queryParams = null)
 * @method static void onInlineQuery(string[]|string|\Closure $action, string $botId = '*', \Closure|string|null $pattern = null)
 * @method static void onMyChatMember(string[]|string|\Closure $action, string $botId = '*', ?bool $isBot = null, ?array<ChatMemberStatus> $allowedStatuses = null)
 * @method static void onChatMember(string[]|string|\Closure $action, string $botId = '*', ?bool $isBot = null, ?array<ChatMemberStatus> $allowedStatuses = null)
 * @method static void onAny(string[]|string|\Closure $action, string $botId = '*')
 * @method static void onFallback(string[]|string|\Closure $action, string $botId = '*')
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
