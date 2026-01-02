<?php

declare(strict_types=1);

namespace HybridGram\Exceptions\Telegram;

use Phptg\BotApi\FailResult;
use Phptg\BotApi\MethodInterface;
use Phptg\BotApi\Transport\ApiResponse;
use Phptg\BotApi\Type\ResponseParameters;

final class TelegramRequestError extends \Exception
{
    private MethodInterface $method;

    private ApiResponse $response;

    private ?string $description;

    private ?ResponseParameters $parameters;

    public function __construct(
        MethodInterface $method,
        ApiResponse $response,
        ?string $description = null,
        ?ResponseParameters $parameters = null,
        ?int $errorCode = null,
        ?\Throwable $previous = null
    ) {
        $this->method = $method;
        $this->response = $response;
        $this->description = $description;
        $this->parameters = $parameters;

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

        if (!empty($response->body)) {
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
