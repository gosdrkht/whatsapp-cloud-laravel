<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

/**
 * Strongly-typed DTO representing a WhatsApp message delivery/read/failed status webhook update.
 */
final class MessageStatus
{
    /**
     * @param  array<int, array<string, mixed>>  $errors
     */
    public function __construct(
        public readonly string $id,
        public readonly string $recipientId,
        public readonly string $status,
        public readonly int $timestamp,
        public readonly ?string $phoneNumberId,
        public readonly ?array $conversation,
        public readonly ?array $pricing,
        public readonly array $errors,
        public readonly array $raw,
    ) {}

    public static function fromWebhook(array $status, array $metadata = []): self
    {
        return new self(
            id: (string) ($status['id'] ?? ''),
            recipientId: (string) ($status['recipient_id'] ?? ''),
            status: (string) ($status['status'] ?? ''),
            timestamp: (int) ($status['timestamp'] ?? 0),
            phoneNumberId: isset($metadata['phone_number_id']) ? (string) $metadata['phone_number_id'] : null,
            conversation: is_array($status['conversation'] ?? null) ? $status['conversation'] : null,
            pricing: is_array($status['pricing'] ?? null) ? $status['pricing'] : null,
            errors: is_array($status['errors'] ?? null) ? $status['errors'] : [],
            raw: $status,
        );
    }

    public function pricingCategory(): ?string
    {
        return isset($this->pricing['category']) ? (string) $this->pricing['category'] : null;
    }

    public function isBillable(): bool
    {
        return (bool) ($this->pricing['billable'] ?? false);
    }

    public function firstErrorMessage(): ?string
    {
        if ($this->errors === []) {
            return null;
        }

        $first = $this->errors[0];

        return (string) ($first['message'] ?? $first['title'] ?? 'Unknown error');
    }

    public function firstErrorCode(): ?int
    {
        if ($this->errors === [] || ! isset($this->errors[0]['code'])) {
            return null;
        }

        return (int) $this->errors[0]['code'];
    }
}
