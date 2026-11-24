<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

/**
 * Verifies Meta webhook X-Hub-Signature-256 headers against the original raw request body.
 */
final class SignatureVerifier
{
    public static function sign(string $rawPayload, string $appSecret): string
    {
        return 'sha256=' . hash_hmac('sha256', $rawPayload, $appSecret);
    }

    public static function verify(string $rawPayload, ?string $signatureHeader, string $appSecret): bool
    {
        if ($signatureHeader === null || $signatureHeader === '' || $appSecret === '') {
            return false;
        }

        if (! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = self::sign($rawPayload, $appSecret);

        return hash_equals($expected, $signatureHeader);
    }
}
