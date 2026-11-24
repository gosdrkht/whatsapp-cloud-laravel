<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Client;

use InvalidArgumentException;

final class WhatsAppConfig
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $phoneNumberId,
        public readonly string $businessAccountId = '',
        public readonly string $apiVersion = 'v21.0',
        public readonly string $baseUrl = 'https://graph.facebook.com',
        public readonly int $timeout = 30,
        public readonly int $connectTimeout = 10,
        public readonly int $retryTimes = 3,
        public readonly int $retrySleepMs = 250,
        public readonly string $appSecret = '',
        public readonly string $verifyToken = '',
    ) {}

    /**
     * Build a WhatsAppConfig instance from a Laravel configuration array.
     */
    public static function fromArray(array $config): self
    {
        return new self(
            accessToken: (string) ($config['access_token'] ?? ''),
            phoneNumberId: (string) ($config['phone_number_id'] ?? ''),
            businessAccountId: (string) ($config['business_account_id'] ?? ''),
            apiVersion: (string) ($config['api_version'] ?? 'v21.0'),
            baseUrl: rtrim((string) ($config['base_url'] ?? 'https://graph.facebook.com'), '/'),
            timeout: (int) ($config['http']['timeout'] ?? 30),
            connectTimeout: (int) ($config['http']['connect_timeout'] ?? 10),
            retryTimes: (int) ($config['http']['retry_times'] ?? 3),
            retrySleepMs: (int) ($config['http']['retry_sleep_ms'] ?? 250),
            appSecret: (string) ($config['webhook']['app_secret'] ?? ''),
            verifyToken: (string) ($config['webhook']['verify_token'] ?? ''),
        );
    }

    public function endpoint(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . trim($this->apiVersion, '/') . '/' . ltrim($path, '/');
    }

    public function messagesEndpoint(?string $phoneNumberId = null): string
    {
        $id = $phoneNumberId ?: $this->phoneNumberId;

        if ($id === '') {
            throw new InvalidArgumentException('WhatsApp phone_number_id is not configured.');
        }

        return $this->endpoint("{$id}/messages");
    }

    public function mediaEndpoint(?string $phoneNumberId = null): string
    {
        $id = $phoneNumberId ?: $this->phoneNumberId;

        if ($id === '') {
            throw new InvalidArgumentException('WhatsApp phone_number_id is not configured.');
        }

        return $this->endpoint("{$id}/media");
    }

    public function templatesEndpoint(?string $businessAccountId = null): string
    {
        $id = $businessAccountId ?: $this->businessAccountId;

        if ($id === '') {
            throw new InvalidArgumentException('WhatsApp business_account_id is not configured.');
        }

        return $this->endpoint("{$id}/message_templates");
    }

    public function withPhoneNumberId(string $phoneNumberId): self
    {
        return new self(
            accessToken: $this->accessToken,
            phoneNumberId: $phoneNumberId,
            businessAccountId: $this->businessAccountId,
            apiVersion: $this->apiVersion,
            baseUrl: $this->baseUrl,
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            retryTimes: $this->retryTimes,
            retrySleepMs: $this->retrySleepMs,
            appSecret: $this->appSecret,
            verifyToken: $this->verifyToken,
        );
    }

    public function withAccessToken(string $accessToken): self
    {
        return new self(
            accessToken: $accessToken,
            phoneNumberId: $this->phoneNumberId,
            businessAccountId: $this->businessAccountId,
            apiVersion: $this->apiVersion,
            baseUrl: $this->baseUrl,
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            retryTimes: $this->retryTimes,
            retrySleepMs: $this->retrySleepMs,
            appSecret: $this->appSecret,
            verifyToken: $this->verifyToken,
        );
    }

    /**
     * Never expose sensitive tokens or secrets in debug dumps.
     */
    public function __debugInfo(): array
    {
        return [
            'accessToken' => $this->accessToken !== '' ? '[REDACTED]' : '',
            'phoneNumberId' => $this->phoneNumberId,
            'businessAccountId' => $this->businessAccountId,
            'apiVersion' => $this->apiVersion,
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
            'connectTimeout' => $this->connectTimeout,
            'retryTimes' => $this->retryTimes,
            'retrySleepMs' => $this->retrySleepMs,
            'appSecret' => $this->appSecret !== '' ? '[REDACTED]' : '',
            'verifyToken' => $this->verifyToken !== '' ? '[REDACTED]' : '',
        ];
    }

    /**
     * Safe array representation with secrets redacted.
     */
    public function toSafeArray(): array
    {
        return $this->__debugInfo();
    }
}
