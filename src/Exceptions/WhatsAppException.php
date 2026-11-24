<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Exceptions;

use RuntimeException;
use Throwable;

class WhatsAppException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly int $httpStatus = 0,
        public readonly ?int $errorCode = null,
        public readonly ?int $errorSubcode = null,
        public readonly ?string $errorType = null,
        public readonly ?string $fbTraceId = null,
        public readonly array $responseBody = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $errorCode ?? $httpStatus, $previous);
    }

    /**
     * Build the most specific exception subclass from a Meta Graph API error payload.
     */
    public static function fromApiResponse(int $httpStatus, array $body, ?Throwable $previous = null): self
    {
        $error = is_array($body['error'] ?? null) ? $body['error'] : [];
        $message = (string) ($error['error_user_msg'] ?? $error['message'] ?? "WhatsApp API request failed with HTTP status {$httpStatus}.");
        $errorCode = isset($error['code']) ? (int) $error['code'] : null;
        $errorSubcode = isset($error['error_subcode']) ? (int) $error['error_subcode'] : null;
        $errorType = isset($error['type']) ? (string) $error['type'] : null;
        $fbTraceId = isset($error['fbtrace_id']) ? (string) $error['fbtrace_id'] : null;

        if ($httpStatus === 401 || $errorCode === 190) {
            return new AuthenticationException(
                message: $message,
                httpStatus: $httpStatus,
                errorCode: $errorCode,
                errorSubcode: $errorSubcode,
                errorType: $errorType,
                fbTraceId: $fbTraceId,
                responseBody: $body,
                previous: $previous,
            );
        }

        if ($httpStatus === 429 || in_array($errorCode, [4, 17, 613, 80007, 130429, 131048, 131056], true)) {
            return new RateLimitException(
                message: $message,
                httpStatus: $httpStatus,
                errorCode: $errorCode,
                errorSubcode: $errorSubcode,
                errorType: $errorType,
                fbTraceId: $fbTraceId,
                responseBody: $body,
                previous: $previous,
            );
        }

        if ($httpStatus === 400 || in_array($errorCode, [100, 131008, 131009, 132000, 132001, 132012], true)) {
            return new ValidationException(
                message: $message,
                httpStatus: $httpStatus,
                errorCode: $errorCode,
                errorSubcode: $errorSubcode,
                errorType: $errorType,
                fbTraceId: $fbTraceId,
                responseBody: $body,
                previous: $previous,
            );
        }

        return new self(
            message: $message,
            httpStatus: $httpStatus,
            errorCode: $errorCode,
            errorSubcode: $errorSubcode,
            errorType: $errorType,
            fbTraceId: $fbTraceId,
            responseBody: $body,
            previous: $previous,
        );
    }

    public function isTransient(): bool
    {
        return $this instanceof RateLimitException || $this->httpStatus >= 500 || $this->httpStatus === 0;
    }
}
