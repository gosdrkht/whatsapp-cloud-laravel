<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Templates;

/**
 * Fluent builder for Meta WhatsApp template message components (header, body, button).
 */
final class TemplateComponent
{
    /**
     * @param  array<int, array<string, mixed>>  $parameters
     */
    private function __construct(
        private readonly string $type,
        private readonly array $parameters = [],
        private readonly ?string $subType = null,
        private readonly ?int $index = null,
    ) {}

    /**
     * Build a header component.
     *
     * @param  array<int, array<string, mixed>>  $parameters
     */
    public static function header(array $parameters): self
    {
        return new self('header', $parameters);
    }

    public static function headerText(string $text, ?string $parameterName = null): self
    {
        return new self('header', [self::textParameter($text, $parameterName)]);
    }

    public static function headerImage(string $urlOrMediaId): self
    {
        return new self('header', [[
            'type' => 'image',
            'image' => self::mediaReference($urlOrMediaId),
        ]]);
    }

    public static function headerVideo(string $urlOrMediaId): self
    {
        return new self('header', [[
            'type' => 'video',
            'video' => self::mediaReference($urlOrMediaId),
        ]]);
    }

    public static function headerDocument(string $urlOrMediaId, ?string $filename = null): self
    {
        $doc = self::mediaReference($urlOrMediaId);
        if ($filename !== null && $filename !== '') {
            $doc['filename'] = $filename;
        }

        return new self('header', [[
            'type' => 'document',
            'document' => $doc,
        ]]);
    }

    /**
     * Build a body component from either simple values, named key=>value pairs, or raw Meta parameter arrays.
     *
     * @param  array<int|string, mixed>  $variables
     */
    public static function body(array $variables): self
    {
        $parameters = [];

        foreach ($variables as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                $parameters[] = $value;
            } elseif (is_string($key)) {
                $parameters[] = self::textParameter((string) $value, $key);
            } else {
                $parameters[] = self::textParameter((string) $value);
            }
        }

        return new self('body', $parameters);
    }

    public static function quickReplyButton(int $index, string $payload): self
    {
        return new self('button', [[
            'type' => 'payload',
            'payload' => $payload,
        ]], 'quick_reply', $index);
    }

    public static function urlButton(int $index, string $text): self
    {
        return new self('button', [[
            'type' => 'text',
            'text' => $text,
        ]], 'url', $index);
    }

    public static function copyCodeButton(int $index, string $couponCode): self
    {
        return new self('button', [[
            'type' => 'coupon_code',
            'coupon_code' => $couponCode,
        ]], 'copy_code', $index);
    }

    public static function textParameter(string $text, ?string $parameterName = null): array
    {
        $param = [
            'type' => 'text',
            'text' => $text,
        ];

        if ($parameterName !== null && $parameterName !== '') {
            $param['parameter_name'] = $parameterName;
        }

        return $param;
    }

    public static function currencyParameter(string $fallbackValue, string $code, int $amount1000): array
    {
        return [
            'type' => 'currency',
            'currency' => [
                'fallback_value' => $fallbackValue,
                'code' => strtoupper($code),
                'amount_1000' => $amount1000,
            ],
        ];
    }

    /**
     * Convenience helper for Sri Lankan Rupees (LKR) currency parameters.
     */
    public static function lkr(float|int $amount): array
    {
        return self::currencyParameter(
            fallbackValue: 'LKR ' . number_format((float) $amount, 2),
            code: 'LKR',
            amount1000: (int) round(((float) $amount) * 1000),
        );
    }

    public static function dateTimeParameter(string $fallbackValue): array
    {
        return [
            'type' => 'date_time',
            'date_time' => [
                'fallback_value' => $fallbackValue,
            ],
        ];
    }

    /**
     * Convenience helper formatting a date/time in Asia/Colombo timezone for Meta date_time parameters.
     */
    public static function colomboDateTime(\DateTimeInterface|string $dateTime, string $format = 'Y-m-d h:i A'): array
    {
        $tz = new \DateTimeZone('Asia/Colombo');

        $dt = $dateTime instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($dateTime)->setTimezone($tz)
            : new \DateTimeImmutable($dateTime, $tz);

        return self::dateTimeParameter($dt->format($format));
    }

    public function toArray(): array
    {
        $component = [
            'type' => $this->type,
        ];

        if ($this->subType !== null) {
            $component['sub_type'] = $this->subType;
        }

        if ($this->index !== null) {
            $component['index'] = (string) $this->index;
        }

        if ($this->parameters !== []) {
            $component['parameters'] = $this->parameters;
        }

        return $component;
    }

    private static function mediaReference(string $urlOrMediaId): array
    {
        if (filter_var($urlOrMediaId, FILTER_VALIDATE_URL) !== false) {
            return ['link' => $urlOrMediaId];
        }

        return ['id' => $urlOrMediaId];
    }
}
