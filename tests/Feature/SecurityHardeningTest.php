<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LankaDev\WhatsApp\Exceptions\MediaException;
use LankaDev\WhatsApp\Facades\WhatsApp;

it('refuses to download media over non-HTTPS URLs', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/media_http_only' => Http::response([
            'id' => 'media_http_only',
            'url' => 'http://insecure.example.com/file.pdf',
            'mime_type' => 'application/pdf',
            'sha256' => 'abc',
            'file_size' => 10,
        ], 200),
    ]);

    expect(fn () => WhatsApp::media()->download('media_http_only'))
        ->toThrow(MediaException::class, 'non-HTTPS');
});

it('rejects path traversal and invalid class names in whatsapp:make-notification', function (): void {
    $this->artisan('whatsapp:make-notification', [
        'name' => '../../EvilNotification',
    ])
        ->expectsOutputToContain('Invalid notification class name')
        ->assertFailed();
});

it('sets X-Content-Type-Options nosniff on webhook verification responses', function (): void {
    $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=challenge-999')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSeeText('challenge-999');
});
