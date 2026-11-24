<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Client;

interface HttpClientInterface
{
    /**
     * Perform an HTTP GET request.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     */
    public function get(string $url, array $query = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse;

    /**
     * Perform an HTTP POST request with a JSON payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function post(string $url, array $payload = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse;

    /**
     * Perform an HTTP DELETE request.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, string>  $headers
     */
    public function delete(string $url, array $query = [], array $headers = [], int $timeout = 30, int $connectTimeout = 10): ApiResponse;

    /**
     * Perform an HTTP POST multipart/form-data request (used for media uploads).
     *
     * @param  array<int, array{name: string, contents: mixed, filename?: string, headers?: array<string, string>}>  $multipart
     * @param  array<string, string>  $headers
     */
    public function postMultipart(string $url, array $multipart, array $headers = [], int $timeout = 60, int $connectTimeout = 10): ApiResponse;

    /**
     * Download raw binary content from a URL.
     *
     * @param  array<string, string>  $headers
     */
    public function download(string $url, array $headers = [], int $timeout = 60, int $connectTimeout = 10): ApiResponse;
}
