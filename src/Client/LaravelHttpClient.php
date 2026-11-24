<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use LankaDev\WhatsApp\Exceptions\WhatsAppException;

/**
 * Laravel HTTP client adapter providing full compatibility with Http::fake().
 */
final class LaravelHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    public function get(string $url, array $query = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse
    {
        return $this->execute(fn () => $this->request($headers, $timeout, $connectTimeout)->get($url, $query));
    }

    public function post(string $url, array $payload = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse
    {
        return $this->execute(fn () => $this->request($headers, $timeout, $connectTimeout)->asJson()->post($url, $payload));
    }

    public function delete(string $url, array $query = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse
    {
        return $this->execute(fn () => $this->request($headers, $timeout, $connectTimeout)->delete($url, $query));
    }

    public function postMultipart(string $url, array $multipart, array $headers = [], int $timeout = 60, int $connectTimeout = 10): ApiResponse
    {
        return $this->execute(function () use ($url, $multipart, $headers, $timeout, $connectTimeout) {
            $request = $this->request($headers, $timeout, $connectTimeout)->asMultipart();
            $fields = [];

            foreach ($multipart as $part) {
                if (isset($part['filename'])) {
                    $request = $request->attach(
                        $part['name'],
                        $part['contents'],
                        $part['filename'],
                        $part['headers'] ?? [],
                    );
                } else {
                    $fields[$part['name']] = $part['contents'];
                }
            }

            return $request->post($url, $fields);
        });
    }

    public function download(string $url, array $headers = [], int $timeout = 60, int $connectTimeout = 10): ApiResponse
    {
        return $this->execute(fn () => $this->request($headers, $timeout, $connectTimeout)->get($url));
    }

    private function request(array $headers, int $timeout, int $connectTimeout): PendingRequest
    {
        return $this->http
            ->withHeaders($headers)
            ->timeout($timeout)
            ->connectTimeout($connectTimeout);
    }

    private function execute(callable $callback): ApiResponse
    {
        try {
            $response = $callback();
        } catch (ConnectionException $e) {
            throw new WhatsAppException(
                message: 'HTTP connection error: ' . $e->getMessage(),
                httpStatus: 0,
                previous: $e,
            );
        }

        return new ApiResponse(
            statusCode: $response->status(),
            rawBody: $response->body(),
            headers: $response->headers(),
        );
    }
}
