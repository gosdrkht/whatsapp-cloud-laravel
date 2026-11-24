<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Models\WhatsAppMessageLog;
use LankaDev\WhatsApp\Testing\WebhookFactory;

it('normalizes local Sri Lankan numbers using configured default_country_code', function (): void {
    config()->set('whatsapp.default_country_code', '94');
    $fake = WhatsApp::fake();

    WhatsApp::to('0771234567')->sendText('Ayubowan!');

    $fake->assertSentText('94771234567', 'Ayubowan!');
});

it('sends OTP authentication template, interactive CTA URL, and WhatsApp Flow messages', function (): void {
    $fake = WhatsApp::fake();

    WhatsApp::to('94771234567')->sendOtp('login_otp', '482910');

    WhatsApp::to('94771234567')->sendInteractiveCtaUrl(
        body: 'Your invoice INV-1001 for LKR 12,500 is ready.',
        displayText: 'Pay Invoice',
        url: 'https://pay.example.lk/inv/1001',
        header: 'Invoice Ready',
        footer: 'Powered by LankaDev',
    );

    WhatsApp::to('94771234567')->sendFlow(
        body: 'Book your doctor appointment directly inside WhatsApp.',
        flowId: 'flow_12345',
        flowCta: 'Book Now',
        flowToken: 'tok_abc',
        flowActionPayload: ['screen' => 'SELECT_DOCTOR'],
    );

    $sent = $fake->sent();
    expect($sent)->toHaveCount(3)
        ->and($sent[0]['type'])->toBe('template')
        ->and($sent[0]['template']['name'])->toBe('login_otp')
        ->and($sent[0]['template']['components'])->toHaveCount(2)
        ->and($sent[1]['interactive']['type'])->toBe('cta_url')
        ->and($sent[1]['interactive']['action']['parameters']['url'])->toBe('https://pay.example.lk/inv/1001')
        ->and($sent[2]['interactive']['type'])->toBe('flow')
        ->and($sent[2]['interactive']['action']['parameters']['flow_id'])->toBe('flow_12345');
});

it('tracks the 24-hour customer service window automatically on inbound webhooks', function (): void {
    expect(WhatsApp::isWithinServiceWindow('94771234567'))->toBeFalse();

    $payload = WebhookFactory::incomingText(from: '94771234567', messageId: 'wamid.window_1');
    $signed = WebhookFactory::sign($payload, 'test-app-secret');

    $this->call(
        'POST',
        '/whatsapp/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signed['signature'],
        ],
        $signed['raw'],
    )->assertOk();

    expect(WhatsApp::isWithinServiceWindow('94771234567'))->toBeTrue()
        ->and(WhatsApp::conversationWindow()->remainingSeconds('94771234567'))->toBeGreaterThan(86000);
});

it('persists outbound, inbound, and status updates when database_logging is enabled', function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);
    config()->set('whatsapp.database_logging.enabled', true);

    $migration = require __DIR__ . '/../../database/migrations/create_whatsapp_messages_table.php.stub';
    $migration->up();

    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.db_out_1']],
        ], 200),
    ]);

    WhatsApp::to('94771234567')->sendTemplate('order_confirmation', 'en', ['customer' => 'Kamal']);

    $log = WhatsAppMessageLog::query()->where('wamid', 'wamid.db_out_1')->first();
    expect($log)->not->toBeNull()
        ->and($log->direction)->toBe('outbound')
        ->and($log->template_name)->toBe('order_confirmation')
        ->and($log->status)->toBe('sent');

    // Simulate delivered webhook updating pricing category and delivered_at
    $statusPayload = WebhookFactory::statusUpdate('delivered', 'wamid.db_out_1', '94771234567');
    $signed = WebhookFactory::sign($statusPayload, 'test-app-secret');

    $this->call(
        'POST',
        '/whatsapp/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signed['signature'],
        ],
        $signed['raw'],
    )->assertOk();

    $log->refresh();
    expect($log->status)->toBe('delivered')
        ->and($log->pricing_category)->toBe('utility')
        ->and($log->billable)->toBeTrue()
        ->and($log->delivered_at)->not->toBeNull();
});
