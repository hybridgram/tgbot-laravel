<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use Closure;
use HybridGram\Core\Routing\Attributes\AttributeRouteRegistrar;
use HybridGram\Core\Routing\Attributes\AttributeRouteScanner;
use HybridGram\Core\Routing\RouteData\FallbackData;
use HybridGram\Core\Routing\RouteOptions\ChatMemberOptions;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface;
use HybridGram\Core\State\StateManagerInterface;
use HybridGram\Core\UpdateHelper;
use HybridGram\Telegram\ChatMember\ChatMemberStatus;
use HybridGram\Telegram\Document\MimeType;
use HybridGram\Telegram\Poll\PollType;
use HybridGram\Telegram\TelegramBotApi;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Laravel\SerializableClosure\SerializableClosure;
use Phptg\BotApi\Type\Update\Update;

final class TelegramRouter
{
    private const string CACHE_KEY_PREFIX = 'telegram_routes_';

    public function __construct(public RouteCollection $routes = new RouteCollection) {}

    /**
     * @param array{
     *     for_bot: string,
     *     from_state?: list<string>,
     *     to_state?: string|list<string>|null,
     *     chat_type?: ChatType|list<ChatType>|null,
     *     cache_key?: string|list<string>,
     *     cache_ttl?: int,
     *     middlewares?: array<int, string|object>,
     *     send_action?: ActionType
     * } $attributes
     */
    public function group(array $attributes, \Closure $callback): void
    {
        $group = new RouteGroup($attributes);
        $builder = $group->addAttributesToBuilder(new TelegramRouteBuilder);
        $callback($builder);
    }

    public function forBot(string $botId): TelegramRouteBuilder
    {
        return new TelegramRouteBuilder()->forBot($botId);
    }

    public function addRoute(TelegramRoute $route): TelegramRoute
    {
        return $this->routes->add($route);
    }

    public function register(TelegramRoute $telegramRoute): void
    {
        $this->routes->add($telegramRoute);
    }

    public function resolveActionsByUpdate(Update $update, string $botId): TelegramRoute
    {
        $states = $this->getCurrentStates($update);

        return $this->routes->findRoute($update, $botId, $states);
    }

    private function getCurrentStates(Update $update): RouteStates
    {
        $chat = UpdateHelper::getChatFromUpdate($update);
        if (! $chat) {
            return RouteStates::empty();
        }

        $stateManager = App::get(StateManagerInterface::class);
        $chatState = $stateManager->getChatState($chat);

        $user = UpdateHelper::getUserFromUpdate($update);
        $userState = null;
        if ($user) {
            $userState = $stateManager->getUserState($chat, $user);
        }

        return new RouteStates(
            chatState: $chatState,
            userState: $userState
        );
    }

    public function registerRoutes(string $routesPath): void
    {
        if ($this->loadRoutesFromCache()) {
            return;
        }

        require_once $routesPath;

        $this->registerAttributeRoutes();
    }

    private function registerAttributeRoutes(): void
    {
        $directories = config('hybridgram.attribute_routing.directories', []);
        if (empty($directories)) {
            return;
        }

        $scanner = new AttributeRouteScanner(
            $directories,
            config('hybridgram.attribute_routing.exclude_directories', []),
        );

        $registrar = new AttributeRouteRegistrar;
        $registrar->register($scanner->scan());
    }

    public function fallbackRoute(Update $update, string $botId): TelegramRoute
    {
        return new TelegramRoute(
            type: RouteType::FALLBACK,
            botId: $botId,
            action: function (FallbackData $fallbackData) {
                if (\app()->isLocal()) {
                    $chat = $fallbackData->getChat();
                    $state = $this->getCurrentStates($fallbackData->update);
                    $telegram = App::make(TelegramBotApi::class, ['botId' => $fallbackData->botId]);
                    $chatStateData = json_encode($state->chatState?->getData() ?? []);
                    $userStateData = json_encode($state->userState?->getData() ?? []);
                    if ($chat) {
                        $telegram->sendMessage($chat->id,
                            "Fallback route has been called. User: {$fallbackData->getUser()->id} User state: {$state->userState?->getName()} data: $userStateData Chat: {$fallbackData->getChat()->id} Chat state: {$state->chatState?->getName()} data: $chatStateData"
                        );
                    }
                }
            },
            data: new FallbackData($update, $botId)
        );
    }

    /**
     * Cache the current routes collection
     */
    public function cacheRoutes(): void
    {
        $cacheKey = $this->getCacheKey();
        $routesData = $this->routes->getRoutes();

        $serializableRoutes = $this->prepareRoutesForSerialization($routesData);

        Cache::forever($cacheKey, $serializableRoutes);
    }

    /**
     * Load routes from cache
     */
    public function loadRoutesFromCache(): bool
    {
        $cacheKey = $this->getCacheKey();
        $cachedRoutes = Cache::get($cacheKey);

        if ($cachedRoutes === null) {
            return false;
        }

        // Restore closures from SerializableClosure
        $restoredRoutes = $this->restoreRoutesFromSerialization($cachedRoutes);

        $this->routes = new RouteCollection($restoredRoutes);

        return true;
    }

    /**
     * Clear routes cache
     */
    public function clearRoutesCache(): void
    {
        $cacheKey = $this->getCacheKey();
        Cache::forget($cacheKey);
    }

    /**
     * Get cache key for routes
     */
    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX.'collection';
    }

    /**
     * Prepare routes for serialization by converting closures to SerializableClosure
     *
     * @param  array<string, array<string, list<TelegramRoute>>>  $routesData
     * @return array<string, array<string, list<array<string, mixed>>>>
     */
    private function prepareRoutesForSerialization(array $routesData): array
    {
        $serializableRoutes = [];

        foreach ($routesData as $routeType => $botRoutes) {
            $serializableRoutes[$routeType] = [];

            foreach ($botRoutes as $botId => $routes) {
                $serializableRoutes[$routeType][$botId] = [];

                foreach ($routes as $route) {
                    $serializableRoute = $this->convertRouteToSerializable($route);
                    $serializableRoutes[$routeType][$botId][] = $serializableRoute;
                }
            }
        }

        return $serializableRoutes;
    }

    /**
     * Convert a TelegramRoute to serializable format.
     * Keys: type, botId, action, pattern, middlewares, fromChatState, fromUserState, exceptChatState, exceptUserState, chatTypes, toState, actionType, actionTimeout, cacheTtl, cacheKey, pollOptions, data.
     * Values: RouteType, string, Closure|SerializableClosure, Closure|string|SerializableClosure, array, string|array|null, and other route properties (objects and scalars).
     *
     * @return array<string, mixed>
     */
    private function convertRouteToSerializable(TelegramRoute $route): array
    {
        $routeData = [
            'type' => $route->type,
            'botId' => $route->botId,
            'action' => $route->action,
            'pattern' => $route->pattern,
            'middlewares' => $route->middlewares,
            'fromChatState' => $route->fromChatState,
            'fromUserState' => $route->fromUserState,
            'exceptChatState' => $route->exceptChatState,
            'exceptUserState' => $route->exceptUserState,
            'chatTypes' => $route->chatTypes,
            'toState' => $route->toState,
            'actionType' => $route->actionType,
            'actionTimeout' => $route->actionTimeout,
            'cacheTtl' => $route->cacheTtl,
            'cacheKey' => $route->cacheKey,
            'pollOptions' => $route->pollOptions,
            'data' => $route->data,
        ];

        // Convert closures to SerializableClosure
        if ($route->action instanceof Closure) {
            $routeData['action'] = new SerializableClosure($route->action);
        }

        if ($route->pattern instanceof Closure) {
            $routeData['pattern'] = new SerializableClosure($route->pattern);
        }

        return $routeData;
    }

    /**
     * Restore routes from serialized format.
     *
     * @param  array<string, array<string, list<array<string, mixed>>>>  $serializedRoutes
     * @return array<string, array<string, list<TelegramRoute>>>
     */
    private function restoreRoutesFromSerialization(array $serializedRoutes): array
    {
        $restoredRoutes = [];

        foreach ($serializedRoutes as $routeType => $botRoutes) {
            $restoredRoutes[$routeType] = [];

            foreach ($botRoutes as $botId => $routes) {
                $restoredRoutes[$routeType][$botId] = [];

                foreach ($routes as $routeData) {
                    $restoredRoute = $this->convertSerializableToRoute($routeData);
                    $restoredRoutes[$routeType][$botId][] = $restoredRoute;
                }
            }
        }

        return $restoredRoutes;
    }

    /**
     * Convert serialized route data back to TelegramRoute.
     *
     * @param  array<string, mixed>  $routeData  Same keys as convertRouteToSerializable() output
     */
    private function convertSerializableToRoute(array $routeData): TelegramRoute
    {
        // Restore closures from SerializableClosure
        $action = $routeData['action'];
        if ($action instanceof SerializableClosure) {
            $action = $action->getClosure();
        }

        $pattern = $routeData['pattern'];
        if ($pattern instanceof SerializableClosure) {
            $pattern = $pattern->getClosure();
        }

        return new TelegramRoute(
            type: $routeData['type'],
            botId: $routeData['botId'],
            action: $action,
            pattern: $pattern,
            middlewares: $routeData['middlewares'],
            fromChatState: $routeData['fromChatState'],
            fromUserState: $routeData['fromUserState'] ?? null,
            exceptChatState: $routeData['exceptChatState'] ?? null,
            exceptUserState: $routeData['exceptUserState'] ?? null,
            toState: $routeData['toState'],
            chatTypes: $routeData['chatTypes'],
            actionType: $routeData['actionType'],
            actionTimeout: $routeData['actionTimeout'],
            cacheTtl: $routeData['cacheTtl'],
            cacheKey: $routeData['cacheKey'],
            pollOptions: $routeData['pollOptions'],
            data: $routeData['data'],
        );
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onMessage(\Closure|string|array $action, string $botId = '*', string|\Closure|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onTextMessage($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onBusinessMessageText(\Closure|string|array $action, string $botId = '*', string|\Closure|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onBusinessMessageText($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onCommand(
        \Closure|string|array $action,
        string $botId = '*',
        string|\Closure|null $pattern = null,
        ?Closure $commandParamOptions = null,
    ): void {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onCommand($action, $pattern, $commandParamOptions);
    }

    /**
     * @param  array<MimeType|string>  $documentOptions
     * @param  \Closure|string|string[]  $action
     */
    public function onDocument(\Closure|string|array $action, string $botId = '*', string|\Closure|null $pattern = null, ?array $documentOptions = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onDocument($action, $pattern, $documentOptions);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPoll(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPoll($action, new PollOptions($isAnonymous, $pollType));
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPollClosed(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPollClosed($action, new PollOptions($isAnonymous, $pollType));
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPollAnswered(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPollAnswered($action, new PollOptions($isAnonymous, $pollType));
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPhoto(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPhoto($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPhotoMediaGroup(\Closure|string|array $action, string $botId = '*', string|\Closure|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPhotoMediaGroup($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onVenue(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onVenue($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onLocation(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onLocation($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onAnimation(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onAnimation($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onAudio(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onAudio($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onSticker(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onSticker($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onVideoNote(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onVideoNote($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onVoice(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onVoice($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onStory(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onStory($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPaidMedia(\Closure|string|array $action, string $botId = '*', ?\Closure $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPaidMedia($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onContact(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onContact($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onChecklist(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onChecklist($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onDice(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onDice($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onGame(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onGame($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onInvoice(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onInvoice($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onSuccessfulPayment(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onSuccessfulPayment($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPassportData(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPassportData($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onBusinessConnection(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onBusinessConnection($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onReply(\Closure|string|array $action, string $botId = '*', \Closure|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onReply($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onExternalReply(\Closure|string|array $action, string $botId = '*', \Closure|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onExternalReply($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onQuote(\Closure|string|array $action, string $botId = '*', \Closure|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onQuote($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onReplyToStory(\Closure|string|array $action, string $botId = '*', \Closure|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onReplyToStory($action, $pattern);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onNewChatTitle(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onNewChatTitle($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onNewChatPhoto(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onNewChatPhoto($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onDeleteChatPhoto(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onDeleteChatPhoto($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onMessageAutoDeleteTimerChanged(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onMessageAutoDeleteTimerChanged($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onPinnedMessage(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPinnedMessage($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onGeneralForumTopicEvent(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onGeneralForumTopicEvent($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onBoostAdded(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onBoostAdded($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     * @param  \Closure|string|null  $pattern  Pattern for action
     * @param  array<string, string|null>|array<int, QueryParamInterface>|null  $queryParams  Query parameter filters: key => value for value check, key => null for existence check, or array of QueryParamInterface objects
     */
    public function onCallbackQuery(\Closure|string|array $action, string $botId = '*', \Closure|string|null $pattern = '*', ?array $queryParams = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onCallbackQuery($action, $pattern, $queryParams);
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onAny(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onAny($action);
    }

    /**
     * @param  \Closure|string|string[]  $action
     * @param  array<ChatMemberStatus>|null  $allowedStatuses
     */
    public function onMyChatMember(\Closure|string|array $action, string $botId = '*', ?bool $isBot = null, ?array $allowedStatuses = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onMyChatMember($action, new ChatMemberOptions($isBot, $allowedStatuses));
    }

    /**
     * @param  \Closure|string|string[]  $action
     * @param  array<ChatMemberStatus>|null  $allowedStatuses  Allowed statuses for newChatMember. null - any statuses
     */
    public function onChatMember(\Closure|string|array $action, string $botId = '*', ?bool $isBot = null, ?array $allowedStatuses = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onChatMember($action, new ChatMemberOptions($isBot, $allowedStatuses));
    }

    /**
     * @param  \Closure|string|string[]  $action
     */
    public function onFallback(\Closure|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onFallback($action);
    }
}
