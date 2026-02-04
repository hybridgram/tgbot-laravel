<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\RouteData;

use HybridGram\Core\Routing\CommandHelper;
use HybridGram\Core\Routing\TelegramRoute;
use Illuminate\Support\Str;
use Phptg\BotApi\Type\Update\Update;

/**
 * @property array<string> $commandParams
 */
final readonly class CommandData extends AbstractRouteData
{
    public function __construct(
        Update $update,
        public string $command,
        string $botId,
        public array $commandParams = []
    ) {
        parent::__construct($update, $botId);
    }

    public static function match(Update $update, TelegramRoute $route): ?CommandData
    {
        if (is_null($update->message->text)) {
            return null;
        }

        $text = $update->message->text;

        if (! str_starts_with($text, '/')) {
            return null;
        }

        [$command, $arguments] = self::parseCommandAndArguments($text) ?? [null, null];

        if ($command === null) {
            return null;
        }

        if (is_null($route->pattern) || $route->pattern === '*') {
            return new CommandData($update, $command, $route->botId, $arguments);
        }

        $pattern = is_string($route->pattern) ? mb_ltrim($route->pattern, '/') : $route->pattern;

        if ($route->pattern instanceof \Closure) {
            if (! call_user_func($pattern, $update)) {
                return null;
            }

            return new CommandData($update, $command, $route->botId, $arguments);
        }

        if (Str::is($pattern, $command)) {
            if (! is_null($route->commandParamOptions)) {
                $result = call_user_func($route->commandParamOptions, $update, $arguments);
                if ($result === false || $result === null) {
                    return null;
                }

                if ($result instanceof CommandData) {
                    return $result;
                }
            }

            return new CommandData($update, $command, $route->botId, $arguments);
        }

        return null;
    }

    /**
     * Парсит команду и аргументы из текста сообщения.
     *
     * Поддерживаемые форматы:
     *  - `/command`                 — команда без аргументов
     *  - `/command_arg1_arg2`       — inline-аргументы через `_`
     *
     * Все части (имя и аргументы) приводятся к нижнему регистру и
     * могут содержать только латинские буквы a-z. В случае несоответствия
     * форматам или ограничениям возвращает null.
     *
     * @return array{0:string,1:array<string>}|null
     */
    private static function parseCommandAndArguments(string $text): ?array
    {
        $withoutSlash = mb_substr($text, 1);

        $spaceParts = explode(' ', $withoutSlash, 2);
        $firstToken = $spaceParts[0] ?? '';
        $rest = isset($spaceParts[1]) ? mb_trim($spaceParts[1]) : '';

        if ($firstToken === '') {
            return null;
        }

        if ($rest !== '') {
            return null;
        }

        $segments = explode('_', $firstToken);
        $commandRaw = array_shift($segments) ?? '';

        if ($commandRaw === '') {
            return null;
        }

        $command = mb_strtolower($commandRaw);
        $arguments = array_map(static fn (string $v): string => mb_strtolower($v), array_filter($segments, static fn ($v) => $v !== ''));

        try {
            CommandHelper::make($command, $arguments); // todo validate method
        } catch (\InvalidArgumentException) {
            return null;
        }

        return [$command, $arguments];
    }
}
