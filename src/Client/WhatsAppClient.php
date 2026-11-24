<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Client;

use LankaDev\WhatsApp\Exceptions\AuthenticationException;
use LankaDev\WhatsApp\Exceptions\WhatsAppException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Core framework-independent WhatsApp Business Cloud API client.
 *
 * Handles authentication headers, retries with exponential backoff for transient
 * errors, structured Meta error parsing, and redacted logging.
 */
class WhatsAppClient
{
    private readonly HttpClientInterface $http;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly WhatsAppConfig $config,
        ?HttpClientInterface $http = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->http = $http ?? new GuzzleHttpClient;
        $this->logger = $logger ?? new NullLogger;
    }

    public function config(): WhatsAppConfig
    {
        return $this->config;
    }

    public function withConfig(WhatsAppConfig $config): self
    {
        return new self($config, $this->http, $this->logger);
    }

    /**
     * Send a message payload to the /{phone_number_id}/messages endpoint.
     */
    public function sendMessage(array $payload, ?string $phoneNumberId = null): ApiResponse
    {
        $payload = array_merge(['messaging_product' => 'whatsapp'], $payload);

        return $this->post($this->config->messagesEndpoint($phoneNumberId), $payload);
    }

    /**
     * Mark an incoming message as read.
     */
    public function markAsRead(string $messageId, ?string $phoneNumberId = null): ApiResponse
    {
        return $this->sendMessage([
            'status' => 'read',
            'message_id' => $messageId,
        ], $phoneNumberId);
    }

    /**
     * Mark an incoming message as read and display a typing indicator to the recipient.
     */
    public function sendTypingIndicator(string $messageId, ?string $phoneNumberId = null): ApiResponse
    {
        return $this->sendMessage([
            'status' => 'read',
            'message_id' => $messageId,
            'typing_indicator' => [
                'type' => 'text',
            ],
        ], $phoneNumberId);
    }

    public function get(string $url, array $query = []): ApiResponse
    {
        return $this->requestWithRetry(
            method: 'GET',
            url: $url,
            callback: fn () => $this->http->get(
                url: $url,
                query: $query,
                headers: $this->authHeaders(),
                timeout: $this->config->timeout,
                connectTimeout: $this->config->connectTimeout,
            ),
            context: ['query' => $query],
        );
    }

    public function post(string $url, array $payload = []): ApiResponse
    {
        return $this->requestWithRetry(
            method: 'POST',
            url: $url,
            callback: fn () => $this->http->post(
                url: $url,
                payload: $payload,
                headers: $this->authHeaders(),
                timeout: $this->config->timeout,
                connectTimeout: $this->config->connectTimeout,
            ),
            context: ['payload_type' => $payload['type'] ?? null],
        );
    }

    public function delete(string $url, array $query = []): ApiResponse
    {
        return $this->requestWithRetry(
            method: 'DELETE',
            url: $url,
            callback: fn () => $this->http->delete(
                url: $url,
                query: $query,
                headers: $this->authHeaders(),
                timeout: $this->config->timeout,
                connectTimeout: $this->config->connectTimeout,
            ),
            context: ['query' => $query],
        );
    }

    /**
     * @param  array<int, array{name: string, contents: mixed, filename?: string, headers?: array<string, string>}>  $multipart
     */
    public function postMultipart(string $url, array $multipart): ApiResponse
    {
        return $this->requestWithRetry(
            method: 'POST',
            url: $url,
            callback: fn () => $this->http->postMultipart(
                url: $url,
                multipart: $multipart,
                headers: $this->authHeaders(),
                timeout: max($this->config->timeout, 60),
                connectTimeout: $this->config->connectTimeout,
            ),
            context: ['multipart' => true],
        );
    }

    public function downloadBinary(string $url): ApiResponse
    {
        return $this->requestWithRetry(
            method: 'GET',
            url: $url,
            callback: fn () => $this->http->download(
                url: $url,
                headers: $this->authHeaders(),
                timeout: max($this->config->timeout, 60),
                connectTimeout: $this->config->connectTimeout,
            ),
            context: ['binary_download' => true],
        );
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        if ($this->config->accessToken === '') {
            throw new AuthenticationException(
                message: 'WhatsApp access token is not configured.',
                httpStatus: 401,
            );
        }

        return [
            'Authorization' => 'Bearer ' . $this->config->accessToken,
            'Accept' => 'application/json',
        ];
    }

    private function requestWithRetry(string $method, string $url, callable $callback, array $context = []): ApiResponse
    {
        $maxAttempts = max(1, $this->config->retryTimes);
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                /** @var ApiResponse $response */
                $response = $callback();

                if ($response->successful()) {
                    $this->logger->debug('WhatsApp API request succeeded', array_merge($context, [
                        'method' => $method,
                        'url' => $this->sanitizeUrl($url),
                        'status' => $response->status(),
                        'attempt' => $attempt,
                    ]));

                    return $response;
                }

                $exception = WhatsAppException::fromApiResponse($response->status(), $response->data);

                if ($attempt < $maxAttempts && $exception->isTransient()) {
                    $this->sleepBeforeRetry($attempt);

                    continue;
                }

                $this->logger->error('WhatsApp API request failed', [
                    'method' => $method,
                    'url' => $this->sanitizeUrl($url),
                    'status' => $response->status(),
                    'error_code' => $exception->errorCode,
                    'error_subcode' => $exception->errorSubcode,
                    'fbtrace_id' => $exception->fbTraceId,
                    'message' => $exception->getMessage(),
                ]);

                throw $exception;
            } catch (WhatsAppException $e) {
                if ($attempt < $maxAttempts && $e->isTransient()) {
                    $this->sleepBeforeRetry($attempt);

                    continue;
                }

                throw $e;
            }
        }
    }

    private function sleepBeforeRetry(int $attempt): void
    {
        $sleepMs = $this->config->retrySleepMs * (2 ** ($attempt - 1));
        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }
    }

    /**
     * Strip any accidental access_token query parameter before logging.
     */
    private function sanitizeUrl(string $url): string
    {
        return (string) preg_replace('/([?&]access_token=)[^&]+/i', '$1[REDACTED]', $url);
    }
}
