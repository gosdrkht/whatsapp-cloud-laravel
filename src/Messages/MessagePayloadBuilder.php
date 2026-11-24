<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Messages;

use LankaDev\WhatsApp\Exceptions\ValidationException;
use LankaDev\WhatsApp\Templates\TemplateComponent;
use LankaDev\WhatsApp\Templates\TemplateValidator;

/**
 * Builds and validates Meta WhatsApp Cloud API message payloads.
 */
final class MessagePayloadBuilder
{
    public static function normalizeRecipient(string $to): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $to) ?? '';

        if ($normalized === '' || strlen($normalized) < 7 || strlen($normalized) > 15) {
            throw new ValidationException(
                "Invalid recipient phone number '{$to}'. Provide an E.164 phone number (e.g. '94771234567').",
                422,
            );
        }

        return $normalized;
    }

    public static function text(string $to, string $body, bool $previewUrl = false, ?string $replyToMessageId = null): array
    {
        if (trim($body) === '') {
            throw new ValidationException('Text message body cannot be empty.', 422);
        }

        return self::base($to, 'text', [
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $body,
            ],
        ], $replyToMessageId);
    }

    public static function image(string $to, string $urlOrMediaId, ?string $caption = null, ?string $replyToMessageId = null): array
    {
        $image = self::mediaReference($urlOrMediaId);
        if ($caption !== null && $caption !== '') {
            $image['caption'] = $caption;
        }

        return self::base($to, 'image', ['image' => $image], $replyToMessageId);
    }

    public static function video(string $to, string $urlOrMediaId, ?string $caption = null, ?string $replyToMessageId = null): array
    {
        $video = self::mediaReference($urlOrMediaId);
        if ($caption !== null && $caption !== '') {
            $video['caption'] = $caption;
        }

        return self::base($to, 'video', ['video' => $video], $replyToMessageId);
    }

    public static function document(string $to, string $urlOrMediaId, ?string $filename = null, ?string $caption = null, ?string $replyToMessageId = null): array
    {
        $document = self::mediaReference($urlOrMediaId);
        if ($filename !== null && $filename !== '') {
            $document['filename'] = $filename;
        }
        if ($caption !== null && $caption !== '') {
            $document['caption'] = $caption;
        }

        return self::base($to, 'document', ['document' => $document], $replyToMessageId);
    }

    public static function audio(string $to, string $urlOrMediaId, ?string $replyToMessageId = null): array
    {
        return self::base($to, 'audio', [
            'audio' => self::mediaReference($urlOrMediaId),
        ], $replyToMessageId);
    }

    public static function location(
        string $to,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null,
        ?string $replyToMessageId = null,
    ): array {
        $location = [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($name !== null && $name !== '') {
            $location['name'] = $name;
        }

        if ($address !== null && $address !== '') {
            $location['address'] = $address;
        }

        return self::base($to, 'location', ['location' => $location], $replyToMessageId);
    }

    /**
     * @param  array<int, ContactCard|array<string, mixed>>  $contacts
     */
    public static function contacts(string $to, array $contacts, ?string $replyToMessageId = null): array
    {
        if ($contacts === []) {
            throw new ValidationException('At least one contact card is required.', 422);
        }

        $serialized = array_map(
            static fn (ContactCard|array $contact): array => $contact instanceof ContactCard ? $contact->toArray() : $contact,
            array_values($contacts),
        );

        return self::base($to, 'contacts', ['contacts' => $serialized], $replyToMessageId);
    }

    public static function reaction(string $to, string $messageId, string $emoji): array
    {
        if ($messageId === '') {
            throw new ValidationException('Reaction target message ID cannot be empty.', 422);
        }

        return self::base($to, 'reaction', [
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji,
            ],
        ]);
    }

    /**
     * Build an interactive reply-buttons message (max 3 buttons per Meta spec).
     *
     * Accepts either ['id' => 'Title'] associative map or [['id' => '...', 'title' => '...']] list.
     *
     * @param  array<int|string, mixed>  $buttons
     */
    public static function interactiveButtons(
        string $to,
        string $body,
        array $buttons,
        ?string $header = null,
        ?string $footer = null,
        ?string $replyToMessageId = null,
    ): array {
        if ($buttons === [] || count($buttons) > 3) {
            throw new ValidationException('Interactive button messages must have between 1 and 3 buttons.', 422);
        }

        $formattedButtons = [];
        foreach ($buttons as $key => $button) {
            if (is_array($button) && isset($button['type']) && $button['type'] === 'reply') {
                $formattedButtons[] = $button;
            } elseif (is_array($button)) {
                $id = (string) ($button['id'] ?? $key);
                $title = (string) ($button['title'] ?? '');
                self::assertButtonTitle($title);
                $formattedButtons[] = [
                    'type' => 'reply',
                    'reply' => ['id' => $id, 'title' => $title],
                ];
            } else {
                $title = (string) $button;
                self::assertButtonTitle($title);
                $formattedButtons[] = [
                    'type' => 'reply',
                    'reply' => ['id' => (string) $key, 'title' => $title],
                ];
            }
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $body],
            'action' => ['buttons' => $formattedButtons],
        ];

        if ($header !== null && $header !== '') {
            $interactive['header'] = ['type' => 'text', 'text' => $header];
        }

        if ($footer !== null && $footer !== '') {
            $interactive['footer'] = ['text' => $footer];
        }

        return self::base($to, 'interactive', ['interactive' => $interactive], $replyToMessageId);
    }

    /**
     * Build an interactive list message (max 10 rows total across all sections per Meta spec).
     *
     * @param  array<int, array<string, mixed>>  $sections
     */
    public static function interactiveList(
        string $to,
        string $body,
        string $buttonText,
        array $sections,
        ?string $header = null,
        ?string $footer = null,
        ?string $replyToMessageId = null,
    ): array {
        if (mb_strlen($buttonText) === 0 || mb_strlen($buttonText) > 20) {
            throw new ValidationException('Interactive list button text must be between 1 and 20 characters.', 422);
        }

        if ($sections === []) {
            throw new ValidationException('Interactive list messages require at least one section.', 422);
        }

        $totalRows = 0;
        foreach ($sections as $section) {
            $rows = $section['rows'] ?? [];
            $totalRows += is_array($rows) ? count($rows) : 0;
        }

        if ($totalRows === 0 || $totalRows > 10) {
            throw new ValidationException('Interactive list messages must have between 1 and 10 rows across all sections.', 422);
        }

        $interactive = [
            'type' => 'list',
            'body' => ['text' => $body],
            'action' => [
                'button' => $buttonText,
                'sections' => array_values($sections),
            ],
        ];

        if ($header !== null && $header !== '') {
            $interactive['header'] = ['type' => 'text', 'text' => $header];
        }

        if ($footer !== null && $footer !== '') {
            $interactive['footer'] = ['text' => $footer];
        }

        return self::base($to, 'interactive', ['interactive' => $interactive], $replyToMessageId);
    }

    /**
     * Build a template message payload.
     *
     * Supports simple body variables (associative named params or positional values)
     * as well as explicit TemplateComponent instances or raw Meta component arrays.
     *
     * @param  array<int|string, mixed>  $variables
     * @param  array<int, TemplateComponent|array<string, mixed>>|null  $components
     */
    public static function template(
        string $to,
        string $name,
        string $language = 'en',
        array $variables = [],
        ?array $components = null,
        ?string $replyToMessageId = null,
    ): array {
        TemplateValidator::validateName($name);
        TemplateValidator::validateLanguage($language);

        $resolvedComponents = [];

        if ($components !== null) {
            foreach ($components as $component) {
                $resolvedComponents[] = $component instanceof TemplateComponent
                    ? $component->toArray()
                    : $component;
            }
        }

        if ($variables !== []) {
            $resolvedComponents[] = TemplateComponent::body($variables)->toArray();
        }

        if ($resolvedComponents !== []) {
            TemplateValidator::validateComponents($resolvedComponents);
        }

        $template = [
            'name' => $name,
            'language' => ['code' => $language],
        ];

        if ($resolvedComponents !== []) {
            $template['components'] = $resolvedComponents;
        }

        return self::base($to, 'template', ['template' => $template], $replyToMessageId);
    }

    private static function base(string $to, string $type, array $extra, ?string $replyToMessageId = null): array
    {
        $payload = array_merge([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizeRecipient($to),
            'type' => $type,
        ], $extra);

        if ($replyToMessageId !== null && $replyToMessageId !== '') {
            $payload['context'] = ['message_id' => $replyToMessageId];
        }

        return $payload;
    }

    private static function mediaReference(string $urlOrMediaId): array
    {
        if (trim($urlOrMediaId) === '') {
            throw new ValidationException('Media URL or media ID cannot be empty.', 422);
        }

        if (filter_var($urlOrMediaId, FILTER_VALIDATE_URL) !== false) {
            return ['link' => $urlOrMediaId];
        }

        return ['id' => $urlOrMediaId];
    }

    private static function assertButtonTitle(string $title): void
    {
        if (trim($title) === '' || mb_strlen($title) > 20) {
            throw new ValidationException(
                "Interactive button title '{$title}' must be between 1 and 20 characters.",
                422,
            );
        }
    }
}
