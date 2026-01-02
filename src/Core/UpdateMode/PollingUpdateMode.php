<?php

declare(strict_types=1);

namespace HybridGram\Core\UpdateMode;

use HybridGram\Core\MediaGroup\MediaGroupGrouper;
use HybridGram\Core\UpdateHelper;
use HybridGram\Exceptions\Telegram\TelegramRequestError;
use Illuminate\Console\Command;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\Type\Update\Update;

final class PollingUpdateMode extends AbstractUpdateMode
{
    protected int $offset = 1;

    private ?Command $command = null;

    public function setCommand(?Command $command): void
    {
        $this->command = $command;
    }

    public function run(?Update $update = null): void
    {
        while (true) {
            try {
                $this->startPolling();
                sleep(1);
            } catch (\Throwable $e) {
                $this->handleError($e);
                sleep(1);
            }
        }
    }

    public function type(): UpdateModeEnum
    {
        return UpdateModeEnum::POLLING;
    }

    /**
     * @internal
     */
    public function startPolling(): void
    {
        $updates = $this->botApi->getUpdates(
            $this->offset,
            $this->botConfig->pollingConfig->limit,
            $this->botConfig->pollingConfig->timeout,
            $this->botConfig->pollingConfig->allowedUpdates,
        ); // todo баг тут работает только после второго сообщения

        if ($updates instanceof FailResult) {
            if ($updates->errorCode === 409) {
                $this->handleWebhookConflict($updates);
                return;
            }

            $error = TelegramRequestError::fromFailResult($updates);
            $this->logError($error, $updates->response->body ?? null);
            throw $error;
        }

        if (empty($updates)) {
            // Prevent busy-loop if someone configured timeout=0.
            if ($this->botConfig->pollingConfig->timeout <= 0) {
                usleep(200_000);
            }
            return;
        }

        if ($this->offset === 1) {
            /** @var Update $lastUpdate */
            $firstUpdate = $updates[0];
            $this->offset = $firstUpdate->updateId;

        }

        /** @var Update[] $updates */
        $groupedUpdates = MediaGroupGrouper::groupUpdates($updates);

        foreach ($groupedUpdates as $update) {
            try {
                $this->outputUpdateToConsole($update);
                $this->processUpdate($update);
            } catch (\Throwable $e) {
                $this->handleError($e);
            }

            $this->offset = $update->updateId + 1;

            if ($update->message?->mediaGroupId !== null) {
                $clearedCount = MediaGroupGrouper::clearGroupedData($update->message->mediaGroupId);
                if ($clearedCount > 1) {
                    // Skip the rest of the grouped updates from the same batch.
                    // update_id is sequential, so advance by (count - 1) more.
                    $this->offset += $clearedCount - 1;
                }
            }
        }

    }

    private function handleWebhookConflict(FailResult $failResult): void
    {
        logger()->warning("Webhook is active. Attempting to delete webhook for bot '{$this->botConfig->botId}'");

        $deleteResult = $this->botApi->deleteWebhook();

        if ($deleteResult instanceof FailResult) {
            $originalError = TelegramRequestError::fromFailResult($failResult);
            $errorMessage = "Failed to delete webhook: {$deleteResult->description}";
            logger()->error($errorMessage);
            $this->outputError($errorMessage);
            throw new \RuntimeException(
                "Cannot start polling: webhook is active and automatic deletion failed. " .
                "Please delete webhook manually using: php artisan react_telegram:webhook:delete --bot={$this->botConfig->botId}. " .
                "Original error: {$originalError->getMessage()}",
                0,
                $originalError
            );
        }

        logger()->info("Webhook deleted successfully for bot '{$this->botConfig->botId}'. Retrying polling...");
    }

    private function handleError(\Throwable $e): void
    {
        $errorMessage = $this->formatErrorMessage($e);
        $responseBody = $this->extractResponseBody($e);

        $this->logError($e, $responseBody);
        $this->outputError($errorMessage, $responseBody);
    }

    private function formatErrorMessage(\Throwable $e): string
    {
        return get_class($e) . ': ' . $e->getMessage();
    }

    private function extractResponseBody(\Throwable $e): ?string
    {
        if ($e instanceof TelegramRequestError) {
            return $e->getResponseBody();
        }

        if ($e->getPrevious()) {
            return $this->extractResponseBody($e->getPrevious());
        }

        return null;
    }

    private function logError(\Throwable $e, ?string $responseBody = null): void
    {
        $context = [
            'bot_id' => $this->botConfig->botId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ];

        if ($responseBody) {
            $context['telegram_response'] = $responseBody;
        }

        if ($e instanceof TelegramRequestError) {
            $context['method'] = $e->getMethodName();
            $context['status_code'] = $e->getStatusCode();
        }

        logger()->error('Telegram polling error', $context);
    }

    private function outputError(string $message, ?string $responseBody = null): void
    {
        if ($this->command) {
            $this->command->error($message);

            if ($responseBody) {
                $this->command->line('<fg=yellow>Telegram API Response:</fg=yellow>');
                $this->command->line($responseBody);
            }
        }
    }

    private function outputUpdateToConsole(Update $update): void
    {
        if ($this->command === null) {
            return;
        }

        $logUpdates = $this->getCommandBoolOption('log-updates') || $this->getCommandBoolOption('full');
        if (!$logUpdates) {
            return;
        }

        $type = UpdateHelper::getUpdateTypeEnum($update)->value ?? 'unknown';
        $chatId = UpdateHelper::getChatFromUpdate($update)?->id;
        $userId = UpdateHelper::getUserFromUpdate($update)?->id;

        $summary = $this->extractUpdateSummary($update);
        $summaryPart = $summary !== '' ? " {$summary}" : '';

        $this->command->line(sprintf(
            '<fg=gray>[update]</fg=gray> bot=%s update_id=%d type=%s chat=%s from=%s%s',
            $this->botConfig->botId,
            $update->updateId,
            $type,
            $chatId === null ? '-' : (string) $chatId,
            $userId === null ? '-' : (string) $userId,
            $summaryPart,
        ));

        if ($this->getCommandBoolOption('full')) {
            $payload = $this->encodeUpdatePayload($update);
            $this->command->line($payload);
        }
    }

    private function extractUpdateSummary(Update $update): string
    {
        $text = null;

        if ($update->message?->text !== null) {
            $text = $update->message->text;
        } elseif ($update->message?->caption !== null) {
            $text = $update->message->caption;
        } elseif ($update->callbackQuery?->data !== null) {
            $text = $update->callbackQuery->data;
        } elseif ($update->inlineQuery?->query !== null) {
            $text = $update->inlineQuery->query;
        } elseif ($update->chosenInlineResult?->query !== null) {
            $text = $update->chosenInlineResult->query;
        }

        if ($text === null || $text === '') {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        $max = 160;
        if ($this->stringLength($text) > $max) {
            $text = $this->stringSlice($text, 0, $max - 1) . '…';
        }

        return 'text=' . $text;
    }

    private function encodeUpdatePayload(Update $update): string
    {
        $rawDecoded = $update->getRaw(true);

        try {
            $json = json_encode(
                $rawDecoded ?? $update,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            $json = json_encode(
                ['update_id' => $update->updateId],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?: '{"update_id":' . $update->updateId . '}';
        }

        return "<fg=yellow>payload:</fg=yellow>\n" . $json;
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function stringSlice(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, $start, $length) : substr($value, $start, $length);
    }

    private function getCommandBoolOption(string $name): bool
    {
        if ($this->command === null) {
            return false;
        }

        try {
            $value = $this->command->option($name);
        } catch (\Throwable) {
            return false;
        }

        return (bool) $value;
    }
}
