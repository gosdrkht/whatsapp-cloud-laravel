<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp;

use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Media\MediaManager;
use LankaDev\WhatsApp\Templates\TemplateManager;

/**
 * Primary service manager exposed via the WhatsApp facade and DI container.
 */
class WhatsAppManager
{
    private ?TemplateManager $templates = null;

    private ?MediaManager $media = null;

    public function __construct(
        private readonly WhatsAppClient $client,
    ) {}

    /**
     * Begin building a message for a specific recipient phone number.
     */
    public function to(string $recipient): PendingMessage
    {
        return new PendingMessage($this, $recipient);
    }

    /**
     * Send a raw message payload through the underlying WhatsAppClient.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendPayload(array $payload, ?string $phoneNumberId = null): ApiResponse
    {
        return $this->client->sendMessage($payload, $phoneNumberId);
    }

    /**
     * Mark an incoming message as read.
     */
    public function markAsRead(string $messageId, ?string $phoneNumberId = null): ApiResponse
    {
        return $this->client->markAsRead($messageId, $phoneNumberId);
    }

    /**
     * Access the Template Management API.
     */
    public function templates(): TemplateManager
    {
        return $this->templates ??= new TemplateManager($this->client);
    }

    /**
     * Access the Media Management API.
     */
    public function media(): MediaManager
    {
        return $this->media ??= new MediaManager($this->client);
    }

    /**
     * Access the underlying framework-independent WhatsAppClient.
     */
    public function client(): WhatsAppClient
    {
        return $this->client;
    }
}
