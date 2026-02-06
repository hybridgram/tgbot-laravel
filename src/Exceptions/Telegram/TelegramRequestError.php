<?php

declare(strict_types=1);

namespace HybridGram\Exceptions\Telegram;

use Phptg\BotApi\FailResult;
use Phptg\BotApi\MethodInterface;
use Phptg\BotApi\Transport\ApiResponse;
use Phptg\BotApi\Type\ResponseParameters;

final class TelegramRequestError extends \Exception
{



    public function __construct(
        /** @var MethodInterface<mixed> */
        private readonly MethodInterface     $method,
        private readonly ApiResponse         $response,
        private readonly ?string             $description = null,
        private readonly ?ResponseParameters $parameters = null,
        ?int                                 $errorCode = null,
        ?\Throwable                          $previous = null
    ) {
        $message = $this->buildDetailedMessage(
            $method,
            $response,
            $description,
            $parameters,
            $errorCode
        );

        parent::__construct($message, $errorCode ?? 0, $previous);
    }

    public static function fromFailResult(FailResult $failResult): self
    {
        return new self(
            $failResult->method,
            $failResult->response,
            $failResult->description,
            $failResult->parameters,
            $failResult->errorCode
        );
    }

    /**
     * @param MethodInterface<mixed> $method
     */
    private function buildDetailedMessage(
        MethodInterface $method,
        ApiResponse $response,
        ?string $description,
        ?ResponseParameters $parameters,
        ?int $errorCode
    ): string {
        $message = "Telegram API request failed for method '{$method->getApiMethod()}'";

        if ($errorCode) {
            $message .= " with error code {$errorCode}";
        }

        if ($description) {
            $message .= ": {$description}";
        }

        $message .= " (HTTP {$response->statusCode})";

        if ($parameters) {
            $params = [];
            if ($parameters->migrateToChatId) {
                $params[] = "migrate_to_chat_id: {$parameters->migrateToChatId}";
            }
            if ($parameters->retryAfter) {
                $params[] = "retry_after: {$parameters->retryAfter}";
            }
            if (! empty($params)) {
                $message .= ' [Parameters: '.implode(', ', $params).']';
            }
        }

        if (! empty($response->body)) {
            $message .= "\nResponse body: {$response->body}";
        }

        return $message;
    }

    public function getMethodName(): string
    {
        return $this->method->getApiMethod();
    }

    public function getStatusCode(): int
    {
        return $this->response->statusCode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getResponseBody(): string
    {
        return $this->response->body;
    }

    public function getParameters(): ?ResponseParameters
    {
        return $this->parameters;
    }
}
