<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp;

use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Jobs\SendWhatsAppMessageJob;
use LankaDev\WhatsApp\Messages\ContactCard;
use LankaDev\WhatsApp\Messages\MessagePayloadBuilder;
use LankaDev\WhatsApp\Templates\TemplateComponent;

/**
 * Fluent message builder bound to a recipient phone number.
 *
 * Example:
 *   WhatsApp::to('94771234567')->sendText('Hello from Sri Lanka!');
 */
class PendingMessage
{
    private ?string $replyToMessageId = null;

    private ?string $phoneNumberId = null;

    private bool $shouldQueue = false;

    private ?string $queueName = null;

    private ?string $queueConnection = null;

    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly string $recipient,
    ) {}

    /**
     * Reply to a specific incoming WhatsApp message ID.
     */
    public function replyTo(string $messageId): self
    {
        $this->replyToMessageId = $messageId;

        return $this;
    }

    /**
     * Override the sender phone number ID for this message.
     */
    public function fromPhoneNumberId(string $phoneNumberId): self
    {
        $this->phoneNumberId = $phoneNumberId;

        return $this;
    }

    /**
     * Dispatch the message asynchronously via Laravel's queue instead of sending synchronously.
     */
    public function queue(?string $queue = null, ?string $connection = null): self
    {
        $this->shouldQueue = true;
        $this->queueName = $queue;
        $this->queueConnection = $connection;

        return $this;
    }

    public function sendText(string $body, bool $previewUrl = false): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::text($this->recipient, $body, $previewUrl, $this->replyToMessageId),
        );
    }

    public function sendImage(string $urlOrMediaId, ?string $caption = null): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::image($this->recipient, $urlOrMediaId, $caption, $this->replyToMessageId),
        );
    }

    public function sendVideo(string $urlOrMediaId, ?string $caption = null): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::video($this->recipient, $urlOrMediaId, $caption, $this->replyToMessageId),
        );
    }

    public function sendDocument(string $urlOrMediaId, ?string $filename = null, ?string $caption = null): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::document($this->recipient, $urlOrMediaId, $filename, $caption, $this->replyToMessageId),
        );
    }

    public function sendAudio(string $urlOrMediaId): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::audio($this->recipient, $urlOrMediaId, $this->replyToMessageId),
        );
    }

    public function sendLocation(float $latitude, float $longitude, ?string $name = null, ?string $address = null): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::location($this->recipient, $latitude, $longitude, $name, $address, $this->replyToMessageId),
        );
    }

    /**
     * @param  array<int, ContactCard|array<string, mixed>>  $contacts
     */
    public function sendContacts(array $contacts): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::contacts($this->recipient, $contacts, $this->replyToMessageId),
        );
    }

    public function sendReaction(string $messageId, string $emoji): ApiResponse
    {
        return $this->dispatch(
            MessagePayloadBuilder::reaction($this->recipient, $messageId, $emoji),
        );
    }

    /**
     * @param  array<int|string, mixed>  $buttons
     */
    public function sendInteractiveButtons(
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): ApiResponse {
        return $this->dispatch(
            MessagePayloadBuilder::interactiveButtons(
                $this->recipient,
                $body,
                $buttons,
                $header,
                $footer,
                $this->replyToMessageId,
            ),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function sendInteractiveList(
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): ApiResponse {
        return $this->dispatch(
            MessagePayloadBuilder::interactiveList(
                $this->recipient,
                $body,
                $buttonText,
                $sections,
                $header,
                $footer,
                $this->replyToMessageId,
            ),
        );
    }

    /**
     * @param  array<int|string, mixed>  $variables
     * @param  array<int, TemplateComponent|array<string, mixed>>|null  $components
     */
    public function sendTemplate(
        string $name,
        string $language = 'en',
        array $variables = [],
        ?array $components = null,
    ): ApiResponse {
        return $this->dispatch(
            MessagePayloadBuilder::template(
                $this->recipient,
                $name,
                $language,
                $variables,
                $components,
                $this->replyToMessageId,
            ),
        );
    }

    /**
     * Send a Meta AUTHENTICATION OTP template message.
     */
    public function sendOtp(
        string $templateName,
        string $code,
        string $language = 'en',
        bool $includeCopyButton = true,
    ): ApiResponse {
        return $this->dispatch(
            MessagePayloadBuilder::otp(
                $this->recipient,
                $templateName,
                $code,
                $language,
                $includeCopyButton,
                $this->replyToMessageId,
            ),
        );
    }

    /**
     * Send an interactive Call-To-Action URL button message.
     */
    public function sendInteractiveCtaUrl(
        string $body,
        string $displayText,
        string $url,
        ?string $header = null,
        ?string $footer = null,
    ): ApiResponse {
        return $this->dispatch(
            MessagePayloadBuilder::interactiveCtaUrl(
                $this->recipient,
                $body,
                $displayText,
                $url,
                $header,
                $footer,
                $this->replyToMessageId,
            ),
        );
    }

    /**
     * Send a WhatsApp Flows interactive message.
     *
     * @param  array<string, mixed>|null  $flowActionPayload
     */
    public function sendFlow(
        string $body,
        string $flowId,
        string $flowCta,
        ?string $flowToken = null,
        string $flowAction = 'navigate',
        ?array $flowActionPayload = null,
        string $mode = 'published',
        ?string $header = null,
        ?string $footer = null,
    ): ApiResponse {
        return $this->dispatch(
            MessagePayloadBuilder::flow(
                $this->recipient,
                $body,
                $flowId,
                $flowCta,
                $flowToken,
                $flowAction,
                $flowActionPayload,
                $mode,
                $header,
                $footer,
                $this->replyToMessageId,
            ),
        );
    }

    /**
     * Send a raw Meta Cloud API message payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function sendRaw(array $payload): ApiResponse
    {
        $payload['to'] ??= MessagePayloadBuilder::normalizeRecipient($this->recipient);

        return $this->dispatch($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(array $payload): ApiResponse
    {
        if ($this->shouldQueue) {
            $job = new SendWhatsAppMessageJob($payload, $this->phoneNumberId);

            if ($this->queueName !== null) {
                $job->onQueue($this->queueName);
            }

            if ($this->queueConnection !== null) {
                $job->onConnection($this->queueConnection);
            }

            dispatch($job);

            return ApiResponse::fake([
                'queued' => true,
                'to' => $payload['to'] ?? $this->recipient,
                'type' => $payload['type'] ?? null,
            ], 202);
        }

        return $this->manager->sendPayload($payload, $this->phoneNumberId);
    }
}
