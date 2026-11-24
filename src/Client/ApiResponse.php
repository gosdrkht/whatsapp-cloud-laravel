<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Client;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * Immutable representation of a WhatsApp Cloud API HTTP response.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class ApiResponse implements ArrayAccess, JsonSerializable
{
    public readonly array $data;

    public function __construct(
        public readonly int $statusCode,
        public readonly string $rawBody,
        public readonly array $headers = [],
        ?array $decodedData = null,
    ) {
        if ($decodedData !== null) {
            $this->data = $decodedData;
        } else {
            $decoded = json_decode($rawBody, true);
            $this->data = is_array($decoded) ? $decoded : [];
        }
    }

    public static function fake(array $data = [], int $statusCode = 200, array $headers = []): self
    {
        return new self(
            statusCode: $statusCode,
            rawBody: json_encode($data, JSON_THROW_ON_ERROR),
            headers: $headers,
            decodedData: $data,
        );
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        $segments = explode('.', $key);
        $current = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function body(): string
    {
        return $this->rawBody;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Convenience accessor for the first WhatsApp message ID (wamid) returned by Meta.
     */
    public function messageId(): ?string
    {
        $id = $this->json('messages.0.id');

        return is_string($id) ? $id : null;
    }

    /**
     * @return list<string>
     */
    public function messageIds(): array
    {
        $messages = $this->json('messages', []);

        if (! is_array($messages)) {
            return [];
        }

        $ids = [];
        foreach ($messages as $message) {
            if (is_array($message) && isset($message['id']) && is_string($message['id'])) {
                $ids[] = $message['id'];
            }
        }

        return $ids;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contacts(): array
    {
        $contacts = $this->json('contacts', []);

        return is_array($contacts) ? $contacts : [];
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('ApiResponse is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('ApiResponse is immutable.');
    }
}
