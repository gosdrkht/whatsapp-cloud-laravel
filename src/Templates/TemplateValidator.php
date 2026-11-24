<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Templates;

use LankaDev\WhatsApp\Exceptions\ValidationException;

/**
 * Validates template names, language codes, and component parameters before dispatch.
 */
final class TemplateValidator
{
    private const VALID_COMPONENT_TYPES = ['header', 'body', 'button'];

    private const VALID_PARAMETER_TYPES = ['text', 'currency', 'date_time', 'image', 'document', 'video', 'payload', 'coupon_code'];

    public static function validateName(string $name): void
    {
        if ($name === '' || ! preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new ValidationException(
                "Invalid template name '{$name}'. Template names must contain only lowercase letters, numbers, and underscores.",
                422,
            );
        }
    }

    public static function validateLanguage(string $language): void
    {
        if ($language === '' || ! preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $language)) {
            throw new ValidationException(
                "Invalid template language code '{$language}'. Expected format like 'en', 'en_US', 'si', or 'ta'.",
                422,
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    public static function validateComponents(array $components): void
    {
        foreach ($components as $index => $component) {
            if (! is_array($component)) {
                throw new ValidationException("Template component at index {$index} must be an array.", 422);
            }

            $type = $component['type'] ?? null;
            if (! is_string($type) || ! in_array($type, self::VALID_COMPONENT_TYPES, true)) {
                throw new ValidationException(
                    "Invalid template component type at index {$index}. Allowed: " . implode(', ', self::VALID_COMPONENT_TYPES) . '.',
                    422,
                );
            }

            if ($type === 'button' && ! isset($component['sub_type'], $component['index'])) {
                throw new ValidationException(
                    "Template button component at index {$index} must define both 'sub_type' and 'index'.",
                    422,
                );
            }

            $parameters = $component['parameters'] ?? [];
            if (! is_array($parameters)) {
                throw new ValidationException("Template component parameters at index {$index} must be an array.", 422);
            }

            foreach ($parameters as $paramIndex => $param) {
                if (! is_array($param) || ! isset($param['type']) || ! in_array($param['type'], self::VALID_PARAMETER_TYPES, true)) {
                    throw new ValidationException(
                        "Invalid template parameter at component {$index}, parameter {$paramIndex}.",
                        422,
                    );
                }

                if ($param['type'] === 'text') {
                    $text = $param['text'] ?? null;
                    if (! is_string($text) || $text === '') {
                        throw new ValidationException(
                            "Template text parameter at component {$index}, parameter {$paramIndex} cannot be empty.",
                            422,
                        );
                    }

                    if (str_contains($text, "\n") || str_contains($text, "\t") || preg_match('/ {5,}/', $text) === 1) {
                        throw new ValidationException(
                            "Template text parameter at component {$index}, parameter {$paramIndex} cannot contain newlines, tabs, or more than 4 consecutive spaces.",
                            422,
                        );
                    }
                }
            }
        }
    }
}
