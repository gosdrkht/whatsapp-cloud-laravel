<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Notifications;

use Illuminate\Notifications\Notification;
use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Exceptions\ValidationException;
use LankaDev\WhatsApp\WhatsAppManager;

/**
 * Laravel Notification Channel for WhatsApp Business Cloud API.
 */
class WhatsAppChannel
{
    public function __construct(
        private readonly WhatsAppManager $whatsapp,
    ) {}

    public function send(object $notifiable, Notification $notification): ?ApiResponse
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            throw new ValidationException(
                sprintf('Notification [%s] must implement a toWhatsApp() method.', $notification::class),
                422,
            );
        }

        $message = $notification->toWhatsApp($notifiable);

        if ($message === null) {
            return null;
        }

        if (is_string($message)) {
            $message = WhatsAppMessage::text($message);
        }

        if (! $message instanceof WhatsAppMessage) {
            throw new ValidationException(
                sprintf('toWhatsApp() on [%s] must return a %s instance or string.', $notification::class, WhatsAppMessage::class),
                422,
            );
        }

        $recipient = $message->getRecipient() ?? $this->resolveRecipient($notifiable, $notification);

        if ($recipient === null || $recipient === '') {
            return null;
        }

        $payload = $message->toPayload($recipient);

        return $this->whatsapp->sendPayload($payload, $message->getPhoneNumberId());
    }

    private function resolveRecipient(object $notifiable, Notification $notification): ?string
    {
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $routed = $notifiable->routeNotificationFor('whatsapp', $notification);
            if (is_string($routed) && $routed !== '') {
                return $routed;
            }
        }

        foreach (['whatsapp_number', 'phone', 'phone_number'] as $attribute) {
            $value = $notifiable->{$attribute} ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
