<?php

declare(strict_types=1);

use LankaDev\WhatsApp\Exceptions\ValidationException;
use LankaDev\WhatsApp\Support\PhoneNumberNormalizer;

it('normalizes Sri Lankan and international phone numbers with default country code', function (): void {
    expect(PhoneNumberNormalizer::normalize('0771234567', '94'))->toBe('94771234567')
        ->and(PhoneNumberNormalizer::normalize('077-123-4567', '94'))->toBe('94771234567')
        ->and(PhoneNumberNormalizer::normalize('771234567', '94'))->toBe('94771234567')
        ->and(PhoneNumberNormalizer::normalize('+94 77 123 4567', '94'))->toBe('94771234567')
        ->and(PhoneNumberNormalizer::normalize('0094771234567', '94'))->toBe('94771234567')
        ->and(PhoneNumberNormalizer::normalize('+44 7700 900077', '94'))->toBe('447700900077');
});

it('rejects empty or invalid phone numbers', function (): void {
    expect(fn () => PhoneNumberNormalizer::normalize(''))->toThrow(ValidationException::class)
        ->and(fn () => PhoneNumberNormalizer::normalize('123'))->toThrow(ValidationException::class);
});
