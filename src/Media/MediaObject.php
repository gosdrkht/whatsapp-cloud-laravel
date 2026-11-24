<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Media;

final class MediaObject
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly string $mimeType,
        public readonly string $sha256,
        public readonly int $fileSize,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            mimeType: (string) ($data['mime_type'] ?? ''),
            sha256: (string) ($data['sha256'] ?? ''),
            fileSize: (int) ($data['file_size'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'mime_type' => $this->mimeType,
            'sha256' => $this->sha256,
            'file_size' => $this->fileSize,
        ];
    }
}
