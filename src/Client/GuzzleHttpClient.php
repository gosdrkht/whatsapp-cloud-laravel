<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use LankaDev\WhatsApp\Exceptions\WhatsAppException;

/**
 * Framework-independent HTTP client backed by Guzzle.
 */
final class GuzzleHttpClient implements HttpClientInterface
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client(['http_errors' => false]);
    }

    public function get(string $url, array $query = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse
    {
        return $this->send('GET', $url, [
            'query' => $query,
            'headers' => $headers,
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
        ]);
    }

    public function post(string $url, array $payload = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse
    {
        return $this->send('POST', $url, [
            'json' => $payload,
            'headers' => $headers,
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
        ]);
    }

    public function delete(string $url, array $query = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse
    {
        return $this->send('DELETE', $url, [
            'query' => $query,
            'headers' => $headers,
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
        ]);
    }

    public function postMultipart(string $url, array $multipart, array $headers = [], int $timeout = 60, int $connectTimeout = 10): ApiResponse
    {
        return $this->send('POST', $url, [
            'multipart' => $multipart,
            'headers' => $headers,
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
        ]);
    }

    public function download(string $url, array $headers = [], int $timeout = 60, int $connectTimeout = 10): ApiResponse
    {
        return $this->send('GET', $url, [
            'headers' => $headers,
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'http_errors' => false,
        ]);
    }

    private function send(string $method, string $url, array $options): ApiResponse
    {
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (GuzzleException $e) {
            throw new WhatsAppException(
                message: 'HTTP transport error: ' . $e->getMessage(),
                httpStatus: 0,
                previous: $e,
            );
        }

        return new ApiResponse(
            statusCode: $response->getStatusCode(),
            rawBody: (string) $response->getBody(),
            headers: $response->getHeaders(),
        );
    }
}
