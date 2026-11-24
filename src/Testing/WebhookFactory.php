<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Testing;

use LankaDev\WhatsApp\Webhooks\SignatureVerifier;

/**
 * Helper factory for constructing realistic Meta WhatsApp webhook payloads and signatures in tests.
 */
final class WebhookFactory
{
    public static function incomingText(
        string $from = '94771234567',
        string $body = 'Hello from Sri Lanka!',
        string $messageId = 'wamid.HBgNOTQ3NzEyMzQ1NjcVAgARGBI5',
        string $contactName = 'Kamal Perera',
        string $phoneNumberId = '123456789012345',
    ): array {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '987654321098765',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '94112345678',
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => $contactName],
                                        'wa_id' => $from,
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => $from,
                                        'id' => $messageId,
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => ['body' => $body],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function statusUpdate(
        string $status = 'delivered',
        string $messageId = 'wamid.HBgNOTQ3NzEyMzQ1NjcVAgARGBI9',
        string $recipientId = '94771234567',
        array $errors = [],
        string $phoneNumberId = '123456789012345',
    ): array {
        $statusPayload = [
            'id' => $messageId,
            'status' => $status,
            'timestamp' => (string) time(),
            'recipient_id' => $recipientId,
            'pricing' => [
                'billable' => true,
                'pricing_model' => 'PMP',
                'category' => 'utility',
            ],
        ];

        if ($errors !== []) {
            $statusPayload['errors'] = $errors;
        }

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '987654321098765',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '94112345678',
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'statuses' => [$statusPayload],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function sign(array $payload, string $appSecret): array
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);

        return [
            'raw' => $raw,
            'signature' => SignatureVerifier::sign($raw, $appSecret),
        ];
    }
}
