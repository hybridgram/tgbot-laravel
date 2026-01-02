<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use HybridGram\Core\Routing\RouteData\FallbackData;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface;
use HybridGram\Core\UpdateHelper;
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

    public function group(array $attributes, callable $callback): void
    {
        $group = new RouteGroup($attributes);
        $builder = $group->addAttributesToBuilder(new TelegramRouteBuilder());
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
        if (!$chat) {
            return RouteStates::empty();
        }

        $stateManager = App::get(\HybridGram\Core\State\StateManagerInterface::class);
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
    }

    public function fallbackRoute(Update $update, string $botId): ?TelegramRoute
    {
        return new TelegramRoute(
            type: RouteType::FALLBACK,
            botId: $botId,
            action: function(FallbackData $fallbackData) {
                if (\app()->isLocal()) {
                    $chat = $fallbackData->getChat();
                    $state = $this->getCurrentStates($fallbackData->update);
                    $telegram = App::make(TelegramBotApi::class, ['botId' => $fallbackData->botId]);
                    $chatStateData = json_encode($state->chatState?->getData() ?? []);
                    $userStateData = json_encode($state->userState?->getData() ?? []);
                    $telegram->sendMessage($chat->id,
                        "Fallback route has been called. User: {$fallbackData->getUser()->id} User state: {$state->userState?->getName()} data: $userStateData Chat: {$fallbackData->getChat()->id} Chat state: {$state->chatState?->getName()} data: $chatStateData"
                    );
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
        return self::CACHE_KEY_PREFIX . 'collection';
    }

    /**
     * Prepare routes for serialization by converting closures to SerializableClosure
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
     * Convert a TelegramRoute to serializable format
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
            'chatType' => $route->chatType,
            'toState' => $route->toState,
            'actionType' => $route->actionType,
            'actionTimeout' => $route->actionTimeout,
            'cacheTtl' => $route->cacheTtl,
            'cacheKey' => $route->cacheKey,
            'pollOptions' => $route->pollOptions,
            'data' => $route->data,
        ];

        // Convert closures to SerializableClosure
        if ($route->action instanceof \Closure) {
            $routeData['action'] = new SerializableClosure($route->action);
        }

        if ($route->pattern instanceof \Closure) {
            $routeData['pattern'] = new SerializableClosure($route->pattern);
        }

        return $routeData;
    }

    /**
     * Restore routes from serialized format
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
     * Convert serialized route data back to TelegramRoute
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
            chatType: $routeData['chatType'],
            actionType: $routeData['actionType'],
            actionTimeout: $routeData['actionTimeout'],
            cacheTtl: $routeData['cacheTtl'],
            cacheKey: $routeData['cacheKey'],
            pollOptions: $routeData['pollOptions'],
            data: $routeData['data'],
        );
    }

    public function onMessage(callable|string|array $action, string $botId = '*', string|callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onMessage($action, $pattern);
    }

    public function onCommand(callable|string|array $action, string $botId = '*', string|callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onCommand($action, $pattern);
    }


    /**
     * @param array<MimeType|string> $documentOptions
     */
    public function onDocument(callable|string|array $action, string $botId = '*', string|callable|null $pattern = null, ?array $documentOptions = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onDocument($action, $pattern, $documentOptions);
    }

    public function onPoll(callable|string|array $action, string $botId = '*', callable|null $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPoll($action, new PollOptions($isAnonymous, $pollType));
    }

    public function onPollClosed(callable|string|array $action, string $botId = '*', callable|null $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPollClosed($action, new PollOptions($isAnonymous, $pollType));
    }

    public function onPollAnswered(callable|string|array $action, string $botId = '*', callable|null $pattern = null, ?bool $isAnonymous = null, ?PollType $pollType = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPollAnswered($action, new PollOptions($isAnonymous, $pollType));
    }

    public function onPhoto(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPhoto($action, $pattern);
    }

    public function onPhotoMediaGroup(callable|string|array $action, string $botId = '*', string|callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPhotoMediaGroup($action, $pattern);
    }

    public function onVenue(callable|string|array $action, string $botId = '*',): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onVenue($action);
    }
    public function onLocation(callable|string|array $action, string $botId = '*',): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onLocation($action);
    }

    public function onAnimation(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onAnimation($action, $pattern);
    }

    public function onAudio(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onAudio($action, $pattern);
    }

    public function onSticker(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onSticker($action, $pattern);
    }

    public function onVideoNote(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onVideoNote($action, $pattern);
    }

    public function onVoice(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onVoice($action, $pattern);
    }

    public function onStory(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onStory($action, $pattern);
    }

    public function onPaidMedia(callable|string|array $action, string $botId = '*', callable|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPaidMedia($action, $pattern);
    }

    public function onContact(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onContact($action);
    }

    public function onChecklist(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onChecklist($action);
    }

    public function onDice(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onDice($action);
    }

    public function onGame(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onGame($action);
    }

    public function onInvoice(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onInvoice($action);
    }

    public function onSuccessfulPayment(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onSuccessfulPayment($action);
    }

    public function onPassportData(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPassportData($action);
    }

    public function onReply(callable|string|array $action, string $botId = '*', callable|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onReply($action, $pattern);
    }

    public function onExternalReply(callable|string|array $action, string $botId = '*', callable|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onExternalReply($action, $pattern);
    }

    public function onQuote(callable|string|array $action, string $botId = '*', callable|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onQuote($action, $pattern);
    }

    public function onReplyToStory(callable|string|array $action, string $botId = '*', callable|string|null $pattern = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onReplyToStory($action, $pattern);
    }

    public function onNewChatMembers(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onNewChatMembers($action);
    }

    public function onLeftChatMember(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onLeftChatMember($action);
    }

    public function onNewChatTitle(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onNewChatTitle($action);
    }

    public function onNewChatPhoto(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onNewChatPhoto($action);
    }

    public function onDeleteChatPhoto(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onDeleteChatPhoto($action);
    }

    public function onMessageAutoDeleteTimerChanged(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onMessageAutoDeleteTimerChanged($action);
    }

    public function onPinnedMessage(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onPinnedMessage($action);
    }

    public function onForumTopicEvent(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onForumTopicEvent($action);
    }

    public function onGeneralForumTopicEvent(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onGeneralForumTopicEvent($action);
    }

    public function onBoostAdded(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onBoostAdded($action);
    }

    /**
     * @param callable|string|array $action
     * @param string $botId
     * @param callable|string|null $pattern Паттерн для action
     * @param array<string, string|null>|array<int, QueryParamInterface>|null $queryParams Фильтры по query параметрам: ключ => значение для проверки значения, ключ => null для проверки наличия, или массив объектов QueryParamInterface
     */
    public function onCallbackQuery(callable|string|array $action, string $botId = '*', callable|string|null $pattern = '*', ?array $queryParams = null): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onCallbackQuery($action, $pattern, $queryParams);
    }

    public function onAny(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onAny($action);
    }

    public function onFallback(callable|string|array $action, string $botId = '*'): void
    {
        new TelegramRouteBuilder()
            ->forBot($botId)
            ->onFallback($action);
    }
}
