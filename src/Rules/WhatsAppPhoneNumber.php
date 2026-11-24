<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use LankaDev\WhatsApp\Exceptions\ValidationException;
use LankaDev\WhatsApp\Support\PhoneNumberNormalizer;

/**
 * Laravel validation rule verifying that an input normalizes to a valid E.164 WhatsApp phone number.
 */
class WhatsAppPhoneNumber implements ValidationRule
{
    public function __construct(
        private readonly ?string $defaultCountryCode = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $fail("The {$attribute} must be a valid WhatsApp phone number.");

            return;
        }

        try {
            PhoneNumberNormalizer::normalize((string) $value, $this->defaultCountryCode);
        } catch (ValidationException) {
            $fail("The {$attribute} must be a valid WhatsApp phone number in E.164 format.");
        }
    }
}
