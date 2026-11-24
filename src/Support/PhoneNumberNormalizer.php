<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Support;

use LankaDev\WhatsApp\Exceptions\ValidationException;

/**
 * Normalizes local and international phone numbers into Meta's E.164 digit-only format.
 *
 * Examples with defaultCountryCode = '94' (Sri Lanka):
 *   '0771234567'      -> '94771234567'
 *   '+94 77 123 4567' -> '94771234567'
 *   '0094771234567'   -> '94771234567'
 *   '94771234567'     -> '94771234567'
 */
final class PhoneNumberNormalizer
{
    public static function normalize(string $phone, ?string $defaultCountryCode = null): string
    {
        $trimmed = trim($phone);

        if ($trimmed === '') {
            throw new ValidationException('Recipient phone number cannot be empty.', 422);
        }

        $hasExplicitInternationalPrefix = str_starts_with($trimmed, '+') || str_starts_with($trimmed, '00');
        $digits = preg_replace('/[^0-9]/', '', $trimmed) ?? '';

        if (str_starts_with($trimmed, '00') && str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $countryCode = $defaultCountryCode ?? self::configuredDefaultCountryCode();
        $countryCode = preg_replace('/[^0-9]/', '', $countryCode) ?? '';

        if (! $hasExplicitInternationalPrefix && $countryCode !== '') {
            if (str_starts_with($digits, '0')) {
                $digits = $countryCode . ltrim($digits, '0');
            } elseif (! str_starts_with($digits, $countryCode) && strlen($digits) <= 9) {
                $digits = $countryCode . $digits;
            }
        }

        if ($digits === '' || strlen($digits) < 7 || strlen($digits) > 15) {
            throw new ValidationException(
                "Invalid recipient phone number '{$phone}'. Provide an E.164 phone number (e.g. '94771234567').",
                422,
            );
        }

        return $digits;
    }

    private static function configuredDefaultCountryCode(): string
    {
        if (function_exists('config')) {
            try {
                $value = config('whatsapp.default_country_code', '');

                return is_string($value) ? $value : '';
            } catch (\Throwable) {
                return '';
            }
        }

        return '';
    }
}
