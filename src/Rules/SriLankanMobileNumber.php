<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use LankaDev\WhatsApp\Exceptions\ValidationException;
use LankaDev\WhatsApp\Support\PhoneNumberNormalizer;

/**
 * Validates Sri Lankan mobile numbers capable of receiving WhatsApp messages
 * (prefixes 070, 071, 072, 074, 075, 076, 077, 078) and rejects landlines such as 011.
 */
class SriLankanMobileNumber implements ValidationRule
{
    private const MOBILE_PATTERN = '/^94(70|71|72|74|75|76|77|78)[0-9]{7}$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $fail("The {$attribute} must be a valid Sri Lankan mobile number.");

            return;
        }

        try {
            $normalized = PhoneNumberNormalizer::normalize((string) $value, '94');
        } catch (ValidationException) {
            $fail("The {$attribute} must be a valid Sri Lankan mobile number.");

            return;
        }

        if (preg_match(self::MOBILE_PATTERN, $normalized) !== 1) {
            $fail("The {$attribute} must be a valid Sri Lankan mobile number (e.g. 0771234567 or 94771234567).");
        }
    }
}
