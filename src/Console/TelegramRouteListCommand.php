<?php

declare(strict_types=1);

namespace HybridGram\Console;

use Closure;
use HybridGram\Core\Routing\TelegramRoute;
use HybridGram\Core\Routing\TelegramRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Terminal;

final class TelegramRouteListCommand extends Command
{
    protected $signature = 'hybridgram:route:list';
    protected $description = 'List all registered Telegram routes';

    /**
     * The verb colors for the command.
     *
     * @var array<string, string>
     */
    protected array $typeColors = [
        'ANY' => 'red',
        'COMMAND' => 'blue',
        'MESSAGE' => 'yellow',
        'POLL' => 'green',
        'POLL_CLOSED' => 'green',
        'POLL_ANSWER' => 'green',
        'PHOTO' => 'cyan',
        'PHOTO_MEDIA_GROUP' => 'cyan',
        'DOCUMENT' => 'magenta',
        'VENUE' => 'yellow',
        'LOCATION' => 'yellow',
        'ANIMATION' => 'cyan',
        'AUDIO' => 'magenta',
        'STICKER' => 'cyan',
        'VIDEO' => 'cyan',
        'VIDEO_NOTE' => 'cyan',
        'VOICE' => 'magenta',
        'STORY' => 'cyan',
        'PAID_MEDIA' => 'cyan',
        'CONTACT' => 'yellow',
        'CHECKLIST' => 'yellow',
        'DICE' => 'yellow',
        'GAME' => 'yellow',
        'TEXT' => 'yellow',
        'INVOICE' => 'yellow',
        'SUCCESSFUL_PAYMENT' => 'green',
        'PASSPORT_DATA' => 'yellow',
        'REPLY_TO_MESSAGE' => 'yellow',
        'FALLBACK' => 'red',
    ];

    public function handle(): int
    {
        /** @var TelegramRouter $router */
        $router = App::get(TelegramRouter::class);

        // Load routes from configuration
        $bots = config('hybridgram.bots', config('bot.bots', []));
        if (empty($bots)) {
            $this->error('No bots configured. Please check your hybridgram configuration.');
            return self::FAILURE;
        }

        foreach ($bots as $bot) {
            if (isset($bot['routes_file']) && file_exists($bot['routes_file'])) {
                $router->registerRoutes($bot['routes_file']);
            }
        }

        $routes = $this->getRoutes($router);

        if (empty($routes)) {
            $this->error('No Telegram routes found.');
            return self::FAILURE;
        }

        $this->displayRoutes($routes);

        return self::SUCCESS;
    }

    /**
     * Get all routes from router and flatten them.
     *
     * @param TelegramRouter $router
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function getRoutes(TelegramRouter $router): array
    {
        $routesData = $router->routes->getRoutes();
        $flattenedRoutes = [];

        foreach ($routesData as $routeType => $botRoutes) {
            foreach ($botRoutes as $botId => $routes) {
                /** @var TelegramRoute $route */
                foreach ($routes as $route) {
                    $flattenedRoutes[] = $this->getRouteInformation($route);
                }
            }
        }

        // Sort by bot_id, then by type, then by pattern
        usort($flattenedRoutes, function ($a, $b) {
            $botCompare = strcmp($a['bot_id'], $b['bot_id']);
            if ($botCompare !== 0) {
                return $botCompare;
            }
            $typeCompare = strcmp($a['type'], $b['type']);
            if ($typeCompare !== 0) {
                return $typeCompare;
            }
            return strcmp($a['pattern'], $b['pattern']);
        });

        // Group by bot_id
        $groupedRoutes = [];
        foreach ($flattenedRoutes as $route) {
            $botId = $route['bot_id'];
            if (!isset($groupedRoutes[$botId])) {
                $groupedRoutes[$botId] = [];
            }
            $groupedRoutes[$botId][] = $route;
        }

        return $groupedRoutes;
    }

    /**
     * Get the route information for a given route.
     *
     * @param TelegramRoute $route
     * @return array<string, mixed>
     */
    protected function getRouteInformation(TelegramRoute $route): array
    {
        return [
            'type' => $route->type->name,
            'pattern' => $this->formatPattern($route->pattern),
            'bot_id' => $route->botId,
            'action' => $this->formatAction($route->action),
            'from_state' => $this->formatState($route->fromChatState),
            'to_state' => $this->formatState($route->toState),
            'middleware' => $this->formatMiddleware($route->middlewares),
            'chat_type' => $route->chatType->name,
            'query_params' => $this->formatQueryParams($route->callbackQueryOptions),
        ];
    }

    /**
     * Format pattern for display.
     *
     * @param Closure|string|null $pattern
     * @return string
     */
    protected function formatPattern(Closure|string|null $pattern): string
    {
        if ($pattern instanceof Closure) {
            return 'Closure';
        }

        return $pattern ?? '*';
    }

    /**
     * Format action for display.
     *
     * @param Closure|string|array|null $action
     * @return string
     */
    protected function formatAction(Closure|string|array|null $action): string
    {
        if ($action instanceof Closure) {
            return 'Closure';
        }

        if (is_array($action)) {
            if (count($action) === 2 && is_string($action[0]) && is_string($action[1])) {
                return $action[0] . '@' . $action[1];
            }
            return 'Array';
        }

        return $action ?? '-';
    }

    /**
     * Format state for display.
     *
     * @param string|array|null $state
     * @return string
     */
    protected function formatState(string|array|null $state): string
    {
        if ($state === null) {
            return '-';
        }

        if (is_array($state)) {
            return implode(', ', $state);
        }

        return $state;
    }

    /**
     * Format middleware for display.
     *
     * @param array $middlewares
     * @return string
     */
    protected function formatMiddleware(array $middlewares): string
    {
        if (empty($middlewares)) {
            return '-';
        }

        $middlewareNames = [];
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $middlewareNames[] = $middleware;
            } elseif (is_object($middleware)) {
                $middlewareNames[] = get_class($middleware);
            } else {
                $middlewareNames[] = gettype($middleware);
            }
        }

        return implode(', ', $middlewareNames);
    }

    /**
     * Format query params for display.
     *
     * @param array<string, string|null>|array<int, \HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface>|null $queryParams
     * @return string
     */
    protected function formatQueryParams(?array $queryParams): string
    {
        if ($queryParams === null || empty($queryParams)) {
            return '-';
        }

        $params = [];
        // Check if array is associative (has string keys) or indexed (has numeric keys)
        $keys = array_keys($queryParams);
        $isAssociative = !empty($keys) && !is_int($keys[0]);
        
        foreach ($queryParams as $key => $item) {
            // Если это объект QueryParamInterface
            if ($item instanceof \HybridGram\Core\Routing\RouteOptions\QueryParams\QueryParamInterface) {
                $paramKey = $item->getKey();
                if ($item instanceof \HybridGram\Core\Routing\RouteOptions\QueryParams\Exist) {
                    $params[] = $paramKey . ' (exist)';
                } elseif ($item instanceof \HybridGram\Core\Routing\RouteOptions\QueryParams\Value) {
                    $reflection = new \ReflectionClass($item);
                    $property = $reflection->getProperty('expectedValue');
                    $property->setAccessible(true);
                    $expectedValue = $property->getValue($item);
                    
                    if (is_callable($expectedValue)) {
                        $params[] = sprintf('%s (callable)', $paramKey);
                    } else {
                        $params[] = sprintf('%s=%s', $paramKey, $expectedValue);
                    }
                } else {
                    $params[] = $paramKey;
                }
            } elseif ($isAssociative) {
                // Старый формат: ассоциативный массив ['paramName' => null] or ['paramName' => 'value']
                $paramName = is_string($key) ? $key : (string) $key;
                if ($item === null) {
                    $params[] = $paramName;
                } else {
                    $params[] = sprintf('%s=%s', $paramName, $item);
                }
            } else {
                // Indexed array: ['paramName'] - treat as parameter name with null value
                $params[] = $item;
            }
        }

        return implode(', ', $params);
    }

    /**
     * Display the route information on the console.
     *
     * @param array<string, array<int, array<string, mixed>>> $groupedRoutes
     * @return void
     */
    protected function displayRoutes(array $groupedRoutes): void
    {
        $terminalWidth = $this->getTerminalWidth();
        $totalRoutes = 0;

        foreach ($groupedRoutes as $botId => $routes) {
            $totalRoutes += count($routes);
            
            $this->line('');
            $this->line(sprintf('<fg=white;options=bold>Bot ID: %s</>', $botId));
            $this->line('');

            $output = $this->formatRoutesForCli($routes, $terminalWidth);
            foreach ($output as $line) {
                $this->line($line);
            }
        }

        $this->line('');
        $routeCountText = sprintf('Showing [%d] routes', $totalRoutes);
        $offset = $terminalWidth - mb_strlen($routeCountText) - 2;
        $spaces = str_repeat(' ', max($offset, 0));
        $this->line($spaces . '<fg=blue;options=bold>' . $routeCountText . '</>');
        $this->line('');
    }

    /**
     * Format routes for CLI output.
     *
     * @param array<int, array<string, mixed>> $routes
     * @param int $terminalWidth
     * @return array<int, string>
     */
    protected function formatRoutesForCli(array $routes, int $terminalWidth): array
    {
        $output = [];
        $maxTypeLength = 0;

        // Calculate max type length
        foreach ($routes as $route) {
            $maxTypeLength = max($maxTypeLength, mb_strlen($route['type']));
        }

        foreach ($routes as $route) {
            $type = $route['type'];
            $pattern = $route['pattern'];
            $botId = $route['bot_id'];
            $action = $route['action'];
            $fromChatState = $route['from_state'];
            $toState = $route['to_state'];
            $middleware = $route['middleware'];
            $queryParams = $route['query_params'];

            // Color the type
            $color = $this->typeColors[$type] ?? 'default';
            $coloredType = sprintf('<fg=%s;options=bold>%s</>', $color, $type);

            // Calculate spacing for type
            $typeSpaces = str_repeat(' ', max($maxTypeLength + 6 - mb_strlen($type), 0));

            // Build pattern with bot_id
            $patternWithBot = $pattern;
            if ($botId !== '*') {
                $patternWithBot = sprintf('%s [bot:%s]', $pattern, $botId);
            }

            // Build action part
            $actionPart = '';
            if ($action !== '-') {
                $actionPart = str_replace('   ', ' › ', $action);
            }

            // Build state part
            $statePart = '';
            if ($fromChatState !== '-' || $toState !== '-') {
                $statePart = sprintf('[%s → %s]', $fromChatState, $toState);
            }

            // Build query params part
            $queryParamsPart = '';
            if ($queryParams !== '-') {
                $queryParamsPart = sprintf('queryParams: [%s]', $queryParams);
            }

            // Combine action, state and query params
            $parts = array_filter([$actionPart, $statePart, $queryParamsPart]);
            $combinedAction = trim(implode(' ', $parts));

            // Smart truncation: prioritize important information
            // Try to keep queryParams and state visible, truncate action if needed
            $mainLineLength = mb_strlen(strip_tags($coloredType)) + mb_strlen($typeSpaces) + mb_strlen($patternWithBot);
            $maxCombinedLength = $terminalWidth - $mainLineLength - 6; // Reserve space for formatting
            
            if ($combinedAction && mb_strlen($combinedAction) > $maxCombinedLength) {
                // Build parts in priority order: queryParams > state > action
                $priorityParts = [];
                if ($queryParamsPart) {
                    $priorityParts[] = $queryParamsPart;
                }
                if ($statePart) {
                    $priorityParts[] = $statePart;
                }
                
                // Calculate space needed for priority parts
                $priorityLength = $priorityParts ? mb_strlen(implode(' ', $priorityParts)) : 0;
                
                // If we can fit priority parts, add truncated action
                if ($maxCombinedLength >= $priorityLength) {
                    $actionLength = $maxCombinedLength - $priorityLength - ($priorityParts ? 1 : 0);
                    if ($actionLength > 15) { // Minimum 15 chars for action to be useful
                        $truncatedAction = mb_substr($actionPart, 0, $actionLength) . '…';
                        $priorityParts[] = $truncatedAction;
                    }
                    $combinedAction = trim(implode(' ', $priorityParts));
                } else {
                    // Not enough space, show only priority parts (queryParams and state)
                    $combinedAction = trim(implode(' ', $priorityParts));
                }
            }

            // Calculate dots after truncation
            $dotsLength = max($terminalWidth - $mainLineLength - mb_strlen($combinedAction) - 6 - ($combinedAction ? 1 : 0), 0);
            $dots = $dotsLength > 0 ? ' ' . str_repeat('.', $dotsLength) : '';

            // Format pattern with yellow for placeholders (if any)
            $formattedPattern = preg_replace('#(\{[^}]+\})#', '<fg=yellow>$1</>', $patternWithBot);

            $fullLine = sprintf(
                '  %s%s<fg=white>%s</><fg=#6C7280>%s %s</>',
                $coloredType,
                $typeSpaces,
                $formattedPattern,
                $dots,
                $combinedAction ?: ''
            );

            $output[] = $fullLine;

            // Add middleware if verbose
            if ($this->output->isVerbose() && $middleware !== '-') {
                $middlewareIndent = str_repeat(' ', $maxTypeLength + 8);
                $middlewareLine = sprintf(
                    '<fg=#6C7280>%s⇂ %s</>',
                    $middlewareIndent,
                    $middleware
                );
                $output[] = $middlewareLine;
            }
        }

        return $output;
    }

    /**
     * Get the terminal width.
     *
     * @return int
     */
    protected function getTerminalWidth(): int
    {
        return (new Terminal)->getWidth();
    }
}

