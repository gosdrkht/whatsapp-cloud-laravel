<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Notifications;

use LankaDev\WhatsApp\Messages\MessagePayloadBuilder;
use LankaDev\WhatsApp\Templates\TemplateComponent;

/**
 * Fluent WhatsApp message builder for Laravel's Notification system.
 *
 * Example:
 *   public function toWhatsApp(object $notifiable): WhatsAppMessage
 *   {
 *       return WhatsAppMessage::template('order_confirmation', 'en', [
 *           'customer' => $notifiable->name,
 *           'order_id' => $this->order->code,
 *       ]);
 *   }
 */
final class WhatsAppMessage
{
    private ?string $recipient = null;

    private ?string $phoneNumberId = null;

    private ?string $replyToMessageId = null;

    /**
     * @param  callable(string, ?string): array<string, mixed>  $payloadBuilder
     */
    private function __construct(
        private readonly mixed $payloadBuilder,
    ) {}

    public static function create(string $body, bool $previewUrl = false): self
    {
        return self::text($body, $previewUrl);
    }

    public static function text(string $body, bool $previewUrl = false): self
    {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::text($to, $body, $previewUrl, $replyTo),
        );
    }

    /**
     * @param  array<int|string, mixed>  $variables
     * @param  array<int, TemplateComponent|array<string, mixed>>|null  $components
     */
    public static function template(
        string $name,
        string $language = 'en',
        array $variables = [],
        ?array $components = null,
    ): self {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::template(
                $to,
                $name,
                $language,
                $variables,
                $components,
                $replyTo,
            ),
        );
    }

    public static function image(string $urlOrMediaId, ?string $caption = null): self
    {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::image($to, $urlOrMediaId, $caption, $replyTo),
        );
    }

    public static function video(string $urlOrMediaId, ?string $caption = null): self
    {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::video($to, $urlOrMediaId, $caption, $replyTo),
        );
    }

    public static function document(string $urlOrMediaId, ?string $filename = null, ?string $caption = null): self
    {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::document($to, $urlOrMediaId, $filename, $caption, $replyTo),
        );
    }

    public static function audio(string $urlOrMediaId): self
    {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::audio($to, $urlOrMediaId, $replyTo),
        );
    }

    public static function location(float $latitude, float $longitude, ?string $name = null, ?string $address = null): self
    {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::location($to, $latitude, $longitude, $name, $address, $replyTo),
        );
    }

    /**
     * @param  array<int|string, mixed>  $buttons
     */
    public static function interactiveButtons(
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
    ): self {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::interactiveButtons(
                $to,
                $body,
                $buttons,
                $header,
                $footer,
                $replyTo,
            ),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public static function interactiveList(
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
    ): self {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::interactiveList(
                $to,
                $body,
                $buttonText,
                $sections,
                $header,
                $footer,
                $replyTo,
            ),
        );
    }

    public static function otp(
        string $templateName,
        string $code,
        string $language = 'en',
        bool $includeCopyButton = true,
    ): self {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::otp(
                $to,
                $templateName,
                $code,
                $language,
                $includeCopyButton,
                $replyTo,
            ),
        );
    }

    public static function interactiveCtaUrl(
        string $body,
        string $displayText,
        string $url,
        ?string $header = null,
        ?string $footer = null,
    ): self {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::interactiveCtaUrl(
                $to,
                $body,
                $displayText,
                $url,
                $header,
                $footer,
                $replyTo,
            ),
        );
    }

    /**
     * @param  array<string, mixed>|null  $flowActionPayload
     */
    public static function flow(
        string $body,
        string $flowId,
        string $flowCta,
        ?string $flowToken = null,
        string $flowAction = 'navigate',
        ?array $flowActionPayload = null,
        string $mode = 'published',
        ?string $header = null,
        ?string $footer = null,
    ): self {
        return new self(
            static fn (string $to, ?string $replyTo): array => MessagePayloadBuilder::flow(
                $to,
                $body,
                $flowId,
                $flowCta,
                $flowToken,
                $flowAction,
                $flowActionPayload,
                $mode,
                $header,
                $footer,
                $replyTo,
            ),
        );
    }

    public function to(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function fromPhoneNumberId(string $phoneNumberId): self
    {
        $this->phoneNumberId = $phoneNumberId;

        return $this;
    }

    public function replyTo(string $messageId): self
    {
        $this->replyToMessageId = $messageId;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getPhoneNumberId(): ?string
    {
        return $this->phoneNumberId;
    }

    /**
     * Build the Meta Cloud API payload for the resolved recipient.
     *
     * @return array<string, mixed>
     */
    public function toPayload(string $recipient): array
    {
        return ($this->payloadBuilder)($recipient, $this->replyToMessageId);
    }
}
