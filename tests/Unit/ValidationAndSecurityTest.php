<?php

declare(strict_types=1);

use LankaDev\WhatsApp\Exceptions\MediaException;
use LankaDev\WhatsApp\Media\MimeTypeValidator;
use LankaDev\WhatsApp\Webhooks\SignatureVerifier;

it('validates supported WhatsApp media MIME types and size limits', function (): void {
    expect(MimeTypeValidator::isSupported('application/pdf'))->toBeTrue()
        ->and(MimeTypeValidator::isSupported('image/jpeg'))->toBeTrue()
        ->and(MimeTypeValidator::isSupported('application/x-executable'))->toBeFalse()
        ->and(MimeTypeValidator::categoryFor('application/pdf'))->toBe('document');

    MimeTypeValidator::validate('image/png', 1_000_000);

    expect(fn () => MimeTypeValidator::validate('image/png', 10_000_000))
        ->toThrow(MediaException::class);

    expect(fn () => MimeTypeValidator::validate('application/zip'))
        ->toThrow(MediaException::class);
});

it('verifies HMAC-SHA256 webhook signatures against raw payloads', function (): void {
    $rawBody = '{"object":"whatsapp_business_account","entry":[]}';
    $secret = 'super-secret-key';

    $signature = SignatureVerifier::sign($rawBody, $secret);

    expect(SignatureVerifier::verify($rawBody, $signature, $secret))->toBeTrue()
        ->and(SignatureVerifier::verify($rawBody . ' ', $signature, $secret))->toBeFalse()
        ->and(SignatureVerifier::verify($rawBody, 'sha256=invalid', $secret))->toBeFalse()
        ->and(SignatureVerifier::verify($rawBody, null, $secret))->toBeFalse();
});
