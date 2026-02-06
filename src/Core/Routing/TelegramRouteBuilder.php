<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use Closure;
use HybridGram\Core\Routing\RouteOptions\ChatMemberOptions;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface;
use HybridGram\Core\Middleware\TelegramRouteMiddlewareInterface;
use HybridGram\Http\Middlewares\SetStateTelegramRouteMiddleware;
use HybridGram\Telegram\Document\MimeType;

final class TelegramRouteBuilder
{
    private TelegramRoute $route;

    private ?Closure $resetCallback = null;

    public function __construct(
    ) {
        $this->route = new TelegramRoute;
    }

    /**
     * Sets callback to restore base route state after registration.
     * Used in groups to preserve attributes.
     */
    public function setResetCallback(Closure $callback): self
    {
        $this->resetCallback = $callback;

        return $this;
    }

    public function routeType(RouteType $routeType): TelegramRouteBuilder
    {
        $this->route->type = $routeType;

        return $this;
    }

    /**
     * @param  Closure|string|array<int, string>  $action  Callable, class/method as string or array of strings (class::method or [Controller::class, 'method'])
     */
    public function action(Closure|string|array $action): TelegramRouteBuilder
    {
        $this->route->action = $action;

        return $this;
    }

    public function pattern(Closure|string|null $pattern): TelegramRouteBuilder
    {
        $this->route->pattern = $pattern ?? '*';

        return $this;
    }

    public function forBot(?string $botId): TelegramRouteBuilder
    {
        $this->route->botId = $botId ?? '*';

        return $this;
    }

    /**
     * @param  array<int, TelegramRouteMiddlewareInterface>  $middlewares  List of middlewares for the route
     */
    public function middlewares(array $middlewares): self
    {
        $this->route->middlewares = array_merge($this->route->middlewares, $middlewares);

        return $this;
    }

    /**
     * @param  array<string|\BackedEnum>  $states
     */
    public function fromChatState(array $states): self
    {
        $normalizedStates = $this->normalizeStates($states);

        $this->route->fromChatState = $normalizedStates;

        return $this;
    }

    /**
     * @param  array<string|\BackedEnum>  $states
     */
    public function fromUserState(array $states): self
    {
        $normalizedStates = $this->normalizeStates($states);

        $this->route->fromUserState = $normalizedStates;

        return $this;
    }

    /**
     * Route will work only if chat is NOT in the specified states
     *
     * @param  array<string|\BackedEnum>  $states
     */
    public function exceptChatState(array $states): self
    {
        $normalizedStates = $this->normalizeStates($states);

        $this->route->exceptChatState = $normalizedStates;

        return $this;
    }

    /**
     * Route will work only if user is NOT in the specified states
     *
     * @param  array<string|\BackedEnum>  $states
     */
    public function exceptUserState(array $states): self
    {
        $normalizedStates = $this->normalizeStates($states);

        $this->route->exceptUserState = $normalizedStates;

        return $this;
    }

    /**
     * Normalizes states: converts BackedEnum to strings
     *
     * @param  array<string|\BackedEnum>  $states
     * @return array<string>
     */
    private function normalizeStates(array $states): array
    {
        return array_map(function ($state) {
            return $state instanceof \BackedEnum ? $state->value : $state;
        }, $states);
    }

    /**
     * @param  array<int|string, mixed>  $data  Arbitrary state data (key — string or int, value — mixed)
     */
    public function toChatState(string|\BackedEnum|null $state, ?int $ttl = null, array $data = []): self
    {
        if ($state === null) {
            $this->route->toState = null;
        } elseif ($state instanceof \BackedEnum) {
            $this->route->toState = $state->value;
        } else {
            $this->route->toState = $state;
        }

        $this->route->middlewares[] = new SetStateTelegramRouteMiddleware($this->route->toState, ttl: $ttl, useUserState: false, data: $data);

        return $this;
    }

    /**
     * @param  array<int|string, mixed>  $data  Arbitrary state data (key — string or int, value — mixed)
     */
    public function toUserState(string|\BackedEnum|null $state, ?int $ttl = null, array $data = []): self
    {
        if ($state === null) {
            $this->route->toState = null;
        } elseif ($state instanceof \BackedEnum) {
            $this->route->toState = $state->value;
        } else {
            $this->route->toState = $state;
        }

        $this->route->middlewares[] = new SetStateTelegramRouteMiddleware($this->route->toState, ttl: $ttl, useUserState: true, data: $data);

        return $this;
    }

    public function sendAction(ActionType $action, int $timeout = 5): self
    {
        $this->route->actionType = $action;
        $this->route->actionTimeout = $timeout;

        return $this;
    }

    public function cache(int $ttl, ?string $key = null): self
    {
        $this->route->cacheTtl = $ttl;
        $this->route->cacheKey = $key;

        return $this;
    }

    /**
     * Sets single chat type (for backward compatibility)
     */
    public function chatType(ChatType $chatType): self
    {
        $this->route->chatTypes = [$chatType];

        return $this;
    }

    /**
     * Sets multiple chat types
     *
     * @param ChatType[]|null  $chatTypes  null means all chat types
     */
    public function chatTypes(?array $chatTypes): self
    {
        if ($chatTypes !== null) {
            foreach ($chatTypes as $chatType) {
                if (! ($chatType instanceof ChatType)) {
                    throw new \InvalidArgumentException('All chatTypes must be instances of '.ChatType::class);
                }
            }
        }
        $this->route->chatTypes = $chatTypes;

        return $this;
    }

    /**
     * @param callable|string|string[] $action
     * @param  Closure|string|null  $pattern  Pattern for command
     * @param  Closure|null  $commandParamOptions  Filter by command parameters:
     *                                             Example: function(Update $update, array $args) { return count($args) > 0; }
     *                                             If returns false or null, route does not match
     *                                             If returns CommandData, it is used
     */
    public function onCommand(
        callable|string|array $action,
        Closure|string|null $pattern = null,
        ?Closure $commandParamOptions = null,
    ): void {
        $this->route->type = RouteType::COMMAND;
        $this->route->action = $action;
        $this->pattern($pattern);
        $this->route->commandParamOptions = $commandParamOptions;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onTextMessage(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::TEXT_MESSAGE;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onBusinessMessageText(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::BUSINESS_MESSAGE_TEXT;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPoll(callable|string|array $action, ?PollOptions $pollOptions = null): void
    {
        $this->route->type = RouteType::POLL;
        $this->route->pollOptions = $pollOptions;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPollClosed(callable|string|array $action, ?PollOptions $pollOptions = null): void
    {
        $this->route->type = RouteType::POLL_CLOSED;
        $this->route->pollOptions = $pollOptions;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPollAnswered(callable|string|array $action, ?PollOptions $pollOptions = null): void
    {
        $this->route->type = RouteType::POLL_ANSWER;
        $this->route->pollOptions = $pollOptions;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPhoto(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::PHOTO;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPhotoMediaGroup(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::PHOTO_MEDIA_GROUP;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     * @param  array<MimeType|string>|null  $documentOptions
     */
    public function onDocument(callable|string|array $action, string|Closure|null $pattern = null, ?array $documentOptions = null): void
    {
        $this->route->type = RouteType::DOCUMENT;
        $this->route->action = $action;
        $this->route->documentOptions = $documentOptions;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onVenue(callable|string|array $action): void
    {
        $this->route->type = RouteType::VENUE;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onLocation(callable|string|array $action): void
    {
        $this->route->type = RouteType::LOCATION;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onAnimation(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::ANIMATION;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onAudio(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::AUDIO;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onSticker(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::STICKER;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onVideoNote(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::VIDEO_NOTE;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onVoice(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::VOICE;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onStory(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::STORY;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPaidMedia(callable|string|array $action, string|Closure|null $pattern = null): void
    {
        $this->route->type = RouteType::PAID_MEDIA;
        $this->route->action = $action;
        $this->pattern($pattern);

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onContact(callable|string|array $action): void
    {
        $this->route->type = RouteType::CONTACT;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onChecklist(callable|string|array $action): void
    {
        $this->route->type = RouteType::CHECKLIST;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onDice(callable|string|array $action): void
    {
        $this->route->type = RouteType::DICE;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onGame(callable|string|array $action): void
    {
        $this->route->type = RouteType::GAME;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onInvoice(callable|string|array $action): void
    {
        $this->route->type = RouteType::INVOICE;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onSuccessfulPayment(callable|string|array $action): void
    {
        $this->route->type = RouteType::SUCCESSFUL_PAYMENT;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPassportData(callable|string|array $action): void
    {
        $this->route->type = RouteType::PASSPORT_DATA;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onBusinessConnection(callable|string|array $action): void
    {
        $this->route->type = RouteType::BUSINESS_CONNECTION;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onReply(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::REPLY_TO_MESSAGE;
        $this->route->action = $action;
        $this->route->pattern = $pattern;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onExternalReply(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::EXTERNAL_REPLY_MESSAGE;
        $this->route->action = $action;
        $this->route->pattern = $pattern;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onQuote(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::QUOTED_MESSAGE;
        $this->route->action = $action;
        $this->route->pattern = $pattern;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onReplyToStory(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::REPLY_TO_STORY;
        $this->route->action = $action;
        $this->route->pattern = $pattern;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     * @param  Closure|string|null  $pattern  Pattern for action
     * @param  array<string, string|null>|array<int, QueryParamInterface>|null  $queryParams  Query parameter filters:
     *                                                                                        Example: queryParams: [new Exist('lang'), new Value('some', '12')]
     */
    public function onCallbackQuery(callable|string|array $action, Closure|string|null $pattern = '*', ?array $queryParams = null): void
    {
        $this->route->type = RouteType::CALLBACK_QUERY;
        $this->route->action = $action;
        $this->route->pattern = $pattern;
        $this->route->callbackQueryOptions = $queryParams;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onNewChatTitle(callable|string|array $action): void
    {
        $this->route->type = RouteType::NEW_CHAT_TITLE;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onNewChatPhoto(callable|string|array $action): void
    {
        $this->route->type = RouteType::NEW_CHAT_PHOTO;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onDeleteChatPhoto(callable|string|array $action): void
    {
        $this->route->type = RouteType::DELETE_CHAT_PHOTO;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onMessageAutoDeleteTimerChanged(callable|string|array $action): void
    {
        $this->route->type = RouteType::AUTO_DELETE_TIMER_CHANGED;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onPinnedMessage(callable|string|array $action): void
    {
        $this->route->type = RouteType::PINNED_MESSAGE;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onForumTopicCreated(callable|string|array $action): void
    {
        $this->route->type = RouteType::FORUM_TOPIC_CREATED;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onForumTopicEdited(callable|string|array $action): void
    {
        $this->route->type = RouteType::FORUM_TOPIC_EDITED;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onForumTopicClosed(callable|string|array $action): void
    {
        $this->route->type = RouteType::FORUM_TOPIC_CLOSED;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onForumTopicReopened(callable|string|array $action): void
    {
        $this->route->type = RouteType::FORUM_TOPIC_REOPENED;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onGeneralForumTopicEvent(callable|string|array $action): void
    {
        $this->route->type = RouteType::GENERAL_FORUM_TOPIC_EVENT;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onBoostAdded(callable|string|array $action): void
    {
        $this->route->type = RouteType::BOOST_ADDED;
        $this->route->action = $action;
        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = null;
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onAny(callable|string|array $action): void
    {
        $this->route->type = RouteType::ANY;
        $this->route->action = $action;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onInlineQuery(callable|string|array $action, Closure|string|null $pattern = null): void
    {
        $this->route->type = RouteType::INLINE_QUERY;
        $this->route->action = $action;
        $this->route->pattern = $pattern;

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onMyChatMember(callable|string|array $action, ?ChatMemberOptions $chatMemberOptions = null): void
    {
        $this->route->type = RouteType::MY_CHAT_MEMBER;
        $this->route->action = $action;
        $this->route->chatMemberOptions = $chatMemberOptions;

        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = ChatType::allExceptPrivate();
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onChatMember(callable|string|array $action, ?ChatMemberOptions $chatMemberOptions = null): void
    {
        $this->route->type = RouteType::CHAT_MEMBER;
        $this->route->action = $action;
        $this->route->chatMemberOptions = $chatMemberOptions;

        if ($this->route->chatTypes === [ChatType::PRIVATE]) {
            $this->route->chatTypes = ChatType::allExceptPrivate();
        }

        $this->register();
    }

    /**
     * @param callable|string|string[] $action
     */
    public function onFallback(callable|string|array $action): void
    {
        $this->route->type = RouteType::FALLBACK;
        $this->route->action = $action;

        $this->register();
    }

    public function register(): void
    {
        /** @var TelegramRouter $router */
        $router = app(TelegramRouter::class);

        $router->register($this->build());

        $this->route = new TelegramRoute;

        if ($this->resetCallback !== null) {
            ($this->resetCallback)($this);
        }
    }

    private function build(): TelegramRoute
    {
        if (is_null($this->route->action)) {
            // todo throw proper error
            throw new \Exception;
        }

        return clone $this->route;
    }
}
