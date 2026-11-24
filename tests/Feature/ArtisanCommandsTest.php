<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LankaDev\WhatsApp\Facades\WhatsApp;

it('runs whatsapp:test Artisan command', function (): void {
    $fake = WhatsApp::fake();

    $this->artisan('whatsapp:test', [
        'to' => '94771234567',
        '--message' => 'CLI test from Colombo',
    ])
        ->expectsOutputToContain('Message sent successfully!')
        ->assertSuccessful();

    $fake->assertSentText('94771234567', 'CLI test from Colombo');
});

it('runs whatsapp:templates Artisan command', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/987654321098765/message_templates*' => Http::response([
            'data' => [
                [
                    'id' => 'tpl_100',
                    'name' => 'order_confirmation',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'status' => 'APPROVED',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('whatsapp:templates', ['--status' => 'approved'])
        ->expectsOutputToContain('order_confirmation')
        ->assertSuccessful();
});

it('runs whatsapp:webhook Artisan command without exposing secrets', function (): void {
    $this->artisan('whatsapp:webhook')
        ->expectsOutputToContain('Configured (hidden)')
        ->doesntExpectOutputToContain('test-access-token')
        ->doesntExpectOutputToContain('test-app-secret')
        ->assertSuccessful();
});
