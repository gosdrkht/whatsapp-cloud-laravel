<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Media;

use LankaDev\WhatsApp\Exceptions\MediaException;

/**
 * Validates media MIME types and file sizes against Meta WhatsApp Cloud API limits.
 */
final class MimeTypeValidator
{
    /**
     * Supported MIME types mapped to their Meta media category and max size in bytes.
     *
     * @var array<string, array{category: string, max_bytes: int}>
     */
    private const SUPPORTED_MIME_TYPES = [
        // Audio (16 MB)
        'audio/aac' => ['category' => 'audio', 'max_bytes' => 16_777_216],
        'audio/amr' => ['category' => 'audio', 'max_bytes' => 16_777_216],
        'audio/mpeg' => ['category' => 'audio', 'max_bytes' => 16_777_216],
        'audio/mp4' => ['category' => 'audio', 'max_bytes' => 16_777_216],
        'audio/ogg' => ['category' => 'audio', 'max_bytes' => 16_777_216],

        // Documents (100 MB)
        'text/plain' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/pdf' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/vnd.ms-powerpoint' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/msword' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/vnd.ms-excel' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['category' => 'document', 'max_bytes' => 104_857_600],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['category' => 'document', 'max_bytes' => 104_857_600],

        // Images (5 MB)
        'image/jpeg' => ['category' => 'image', 'max_bytes' => 5_242_880],
        'image/png' => ['category' => 'image', 'max_bytes' => 5_242_880],

        // Stickers (500 KB)
        'image/webp' => ['category' => 'sticker', 'max_bytes' => 512_000],

        // Videos (16 MB)
        'video/mp4' => ['category' => 'video', 'max_bytes' => 16_777_216],
        'video/3gpp' => ['category' => 'video', 'max_bytes' => 16_777_216],
    ];

    public static function isSupported(string $mimeType): bool
    {
        return isset(self::SUPPORTED_MIME_TYPES[self::normalize($mimeType)]);
    }

    public static function validate(string $mimeType, ?int $fileSizeBytes = null): void
    {
        $normalized = self::normalize($mimeType);

        if (! isset(self::SUPPORTED_MIME_TYPES[$normalized])) {
            throw new MediaException(
                "Unsupported WhatsApp media MIME type '{$mimeType}'.",
                422,
            );
        }

        if ($fileSizeBytes !== null) {
            $maxBytes = self::SUPPORTED_MIME_TYPES[$normalized]['max_bytes'];
            if ($fileSizeBytes > $maxBytes) {
                $maxMb = round($maxBytes / 1_048_576, 2);
                throw new MediaException(
                    "File size ({$fileSizeBytes} bytes) exceeds the WhatsApp Cloud API limit of {$maxMb} MB for '{$normalized}'.",
                    422,
                );
            }
        }
    }

    public static function categoryFor(string $mimeType): ?string
    {
        return self::SUPPORTED_MIME_TYPES[self::normalize($mimeType)]['category'] ?? null;
    }

    private static function normalize(string $mimeType): string
    {
        $parts = explode(';', strtolower(trim($mimeType)));

        return trim($parts[0]);
    }
}
