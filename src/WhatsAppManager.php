<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp;

use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Media\MediaManager;
use LankaDev\WhatsApp\Models\WhatsAppMessageLog;
use LankaDev\WhatsApp\Support\ConversationWindow;
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
        private readonly ?ConversationWindow $conversationWindow = null,
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
        $response = $this->client->sendMessage($payload, $phoneNumberId);

        $this->logOutboundMessage($payload, $response, $phoneNumberId);

        return $response;
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
     * Access the 24-hour customer service window tracker.
     */
    public function conversationWindow(): ?ConversationWindow
    {
        return $this->conversationWindow;
    }

    /**
     * Check whether the given phone number is currently inside Meta's 24-hour customer service window.
     */
    public function isWithinServiceWindow(string $phoneNumber): bool
    {
        return $this->conversationWindow?->isOpen($phoneNumber) ?? false;
    }

    /**
     * Access the underlying framework-independent WhatsAppClient.
     */
    public function client(): WhatsAppClient
    {
        return $this->client;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function logOutboundMessage(array $payload, ApiResponse $response, ?string $phoneNumberId): void
    {
        if (! function_exists('config') || ! config('whatsapp.database_logging.enabled', false)) {
            return;
        }

        WhatsAppMessageLog::query()->create([
            'wamid' => $response->messageId(),
            'direction' => 'outbound',
            'phone_number' => (string) ($payload['to'] ?? ''),
            'phone_number_id' => $phoneNumberId ?: $this->client->config()->phoneNumberId,
            'type' => (string) ($payload['type'] ?? 'unknown'),
            'template_name' => isset($payload['template']['name']) ? (string) $payload['template']['name'] : null,
            'status' => $response->successful() ? 'sent' : 'failed',
            'payload' => $payload,
        ]);
    }
}
