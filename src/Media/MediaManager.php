<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Media;

use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Exceptions\MediaException;

/**
 * Upload, inspect, download, and delete WhatsApp Cloud API media objects.
 */
class MediaManager
{
    public function __construct(
        private readonly WhatsAppClient $client,
    ) {}

    /**
     * Upload a local file to Meta Cloud API and return the response containing the media ID.
     */
    public function upload(string $filePath, ?string $mimeType = null, ?string $phoneNumberId = null): ApiResponse
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new MediaException("Media file not found or not readable: {$filePath}", 422);
        }

        $detectedMime = $mimeType ?: (mime_content_type($filePath) ?: '');
        $fileSize = filesize($filePath);

        MimeTypeValidator::validate($detectedMime, $fileSize !== false ? $fileSize : null);

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new MediaException("Failed to read media file: {$filePath}", 500);
        }

        return $this->client->postMultipart(
            $this->client->config()->mediaEndpoint($phoneNumberId),
            [
                [
                    'name' => 'messaging_product',
                    'contents' => 'whatsapp',
                ],
                [
                    'name' => 'type',
                    'contents' => $detectedMime,
                ],
                [
                    'name' => 'file',
                    'contents' => $contents,
                    'filename' => basename($filePath),
                    'headers' => ['Content-Type' => $detectedMime],
                ],
            ],
        );
    }

    /**
     * Retrieve metadata and temporary download URL for a media ID.
     */
    public function get(string $mediaId, ?string $phoneNumberId = null): MediaObject
    {
        $query = [];
        if ($phoneNumberId !== null && $phoneNumberId !== '') {
            $query['phone_number_id'] = $phoneNumberId;
        }

        $response = $this->client->get(
            $this->client->config()->endpoint($mediaId),
            $query,
        );

        return MediaObject::fromArray($response->toArray());
    }

    /**
     * Retrieve only the temporary download URL for a media ID.
     */
    public function getUrl(string $mediaId, ?string $phoneNumberId = null): string
    {
        return $this->get($mediaId, $phoneNumberId)->url;
    }

    /**
     * Download binary media content by media ID. Optionally persist it to $savePath.
     */
    public function download(string $mediaId, ?string $savePath = null, ?string $phoneNumberId = null): string
    {
        $media = $this->get($mediaId, $phoneNumberId);

        if ($media->url === '') {
            throw new MediaException("Meta did not return a download URL for media ID '{$mediaId}'.", 404);
        }

        $binaryResponse = $this->client->downloadBinary($media->url);
        $bytes = $binaryResponse->body();

        if ($savePath !== null) {
            $directory = dirname($savePath);
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new MediaException("Unable to create target directory: {$directory}", 500);
            }

            if (file_put_contents($savePath, $bytes) === false) {
                throw new MediaException("Unable to write downloaded media to: {$savePath}", 500);
            }
        }

        return $bytes;
    }

    /**
     * Delete a previously uploaded media object.
     */
    public function delete(string $mediaId, ?string $phoneNumberId = null): ApiResponse
    {
        $query = [];
        if ($phoneNumberId !== null && $phoneNumberId !== '') {
            $query['phone_number_id'] = $phoneNumberId;
        }

        return $this->client->delete(
            $this->client->config()->endpoint($mediaId),
            $query,
        );
    }
}
