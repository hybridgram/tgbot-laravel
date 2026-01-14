<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

use HybridGram\Core\MediaGroup\MediaGroupGrouper;
use HybridGram\Models\TelegramUpdate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use HybridGram\Core\Config\BotConfig;
use HybridGram\Core\Routing\TelegramRouter;
use HybridGram\Exceptions\InvalidRouteAction;
use HybridGram\Telegram\Priority;
use Phptg\BotApi\TelegramBotApi as VjikTelegramBotApi;
use Phptg\BotApi\Type\Update\Update;

abstract class AbstractUpdateMode implements UpdateModeInterface
{
    protected readonly VjikTelegramBotApi $botApi;

    public function __construct(protected readonly BotConfig $botConfig)
    {
        $this->botApi = new VjikTelegramBotApi($this->botConfig->token);
    }

    /**
     * @throws InvalidRouteAction
     */
    protected function processUpdate(Update $update): void
    {
        try {
            /** @var TelegramRouter $telegramRouter */
            $telegramRouter = App::get(TelegramRouter::class);
            $telegramRouter->registerRoutes($this->botConfig->routesPath); // todo вытащить в мидлварь?
            $routeWithParams = $telegramRouter->resolveActionsByUpdate($update, $this->botConfig->botId);
            $botId = $routeWithParams->data?->botId ?? $this->botConfig->botId;
            App::instance('telegram.botId', $botId);

            $finalHandler = function (Update $update) use ($routeWithParams) {
                if (is_callable($routeWithParams->action)) {
                    return ($routeWithParams->action)($routeWithParams->data);
                } elseif (is_array($routeWithParams->action)) {
                    if (count($routeWithParams->action) < 2) {
                        throw new InvalidRouteAction($routeWithParams->action);
                    }
                    [$class, $method] = $routeWithParams->action;
                    $instance = App::make($class);
                    return $instance->$method($routeWithParams->data, $update);
                } else {
                    throw new InvalidRouteAction($routeWithParams->action);
                }
            };

            $routeWithParams->executeWithMiddleware($update, $finalHandler);
        } catch (\Throwable $e) {
            logger()->error('Error processing update', [
                'bot_id' => $this->botConfig->botId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
