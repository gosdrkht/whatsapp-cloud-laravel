<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

/**
 * Strongly-typed DTO representing an incoming WhatsApp message received via webhook.
 */
final class IncomingMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $from,
        public readonly string $type,
        public readonly int $timestamp,
        public readonly ?string $contactName,
        public readonly ?string $phoneNumberId,
        public readonly ?string $displayPhoneNumber,
        public readonly array $raw,
    ) {}

    public static function fromWebhook(array $message, array $metadata = [], ?array $contact = null): self
    {
        return new self(
            id: (string) ($message['id'] ?? ''),
            from: (string) ($message['from'] ?? ''),
            type: (string) ($message['type'] ?? 'unknown'),
            timestamp: (int) ($message['timestamp'] ?? 0),
            contactName: isset($contact['profile']['name']) ? (string) $contact['profile']['name'] : null,
            phoneNumberId: isset($metadata['phone_number_id']) ? (string) $metadata['phone_number_id'] : null,
            displayPhoneNumber: isset($metadata['display_phone_number']) ? (string) $metadata['display_phone_number'] : null,
            raw: $message,
        );
    }

    public function text(): ?string
    {
        return match ($this->type) {
            'text' => isset($this->raw['text']['body']) ? (string) $this->raw['text']['body'] : null,
            'button' => isset($this->raw['button']['text']) ? (string) $this->raw['button']['text'] : null,
            'interactive' => $this->interactiveReplyTitle(),
            default => $this->caption(),
        };
    }

    public function caption(): ?string
    {
        $media = $this->raw[$this->type] ?? null;

        return is_array($media) && isset($media['caption']) ? (string) $media['caption'] : null;
    }

    public function mediaId(): ?string
    {
        $media = $this->raw[$this->type] ?? null;

        return is_array($media) && isset($media['id']) ? (string) $media['id'] : null;
    }

    public function mediaMimeType(): ?string
    {
        $media = $this->raw[$this->type] ?? null;

        return is_array($media) && isset($media['mime_type']) ? (string) $media['mime_type'] : null;
    }

    public function interactiveReplyId(): ?string
    {
        $interactive = $this->raw['interactive'] ?? null;
        if (! is_array($interactive)) {
            return null;
        }

        $subType = $interactive['type'] ?? null;
        if (is_string($subType) && isset($interactive[$subType]['id'])) {
            return (string) $interactive[$subType]['id'];
        }

        return null;
    }

    public function interactiveReplyTitle(): ?string
    {
        $interactive = $this->raw['interactive'] ?? null;
        if (! is_array($interactive)) {
            return null;
        }

        $subType = $interactive['type'] ?? null;
        if (is_string($subType) && isset($interactive[$subType]['title'])) {
            return (string) $interactive[$subType]['title'];
        }

        return null;
    }

    /**
     * @return array{latitude: float, longitude: float, name?: string, address?: string}|null
     */
    public function location(): ?array
    {
        if ($this->type !== 'location' || ! is_array($this->raw['location'] ?? null)) {
            return null;
        }

        return $this->raw['location'];
    }

    public function replyToMessageId(): ?string
    {
        return isset($this->raw['context']['id']) ? (string) $this->raw['context']['id'] : null;
    }
}
