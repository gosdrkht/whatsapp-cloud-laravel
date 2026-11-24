<?php

declare(strict_types=1);

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use LankaDev\WhatsApp\Client\WhatsAppConfig;
use LankaDev\WhatsApp\Contracts\TenantCredentialResolver;
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Messages\MessagePayloadBuilder;

it('supports multi-tenant messaging via using() and forTenant() without mutating default config', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/999000111/messages' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.tenant_a']],
        ], 200),
        'https://graph.facebook.com/v21.0/888000222/messages' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.tenant_b']],
        ], 200),
    ]);

    $respA = WhatsApp::using([
        'access_token' => 'tenant-a-token',
        'phone_number_id' => '999000111',
    ])->to('94771234567')->sendText('Hello from Branch A');

    expect($respA->messageId())->toBe('wamid.tenant_a')
        ->and(WhatsApp::client()->config()->phoneNumberId)->toBe('123456789012345');

    app()->bind(TenantCredentialResolver::class, fn () => new class implements TenantCredentialResolver
    {
        public function resolve(mixed $tenant): WhatsAppConfig
        {
            return new WhatsAppConfig(
                accessToken: 'tenant-b-token',
                phoneNumberId: '888000222',
            );
        }
    });

    $respB = WhatsApp::forTenant('clinic-b')->to('94771234567')->sendText('Hello from Clinic B');
    expect($respB->messageId())->toBe('wamid.tenant_b');
});

it('dispatches rate-paced bulk broadcast batches via Laravel Bus', function (): void {
    Bus::fake();

    $recipients = ['94771111111', '94772222222', '94773333333', '94774444444'];

    WhatsApp::broadcast(
        recipients: $recipients,
        payloadFactory: fn (string $to): array => MessagePayloadBuilder::template($to, 'promo_offer', 'en'),
        batchName: 'new-year-promo',
        queue: 'whatsapp-bulk',
        rateLimitPerSecond: 2,
    )->dispatch();

    Bus::assertBatched(function (PendingBatch $batch): bool {
        return $batch->name === 'new-year-promo'
            && $batch->jobs->count() === 4
            && $batch->jobs[0]->delay === null
            && $batch->jobs[2]->delay === 1;
    });
});

it('sends typing indicators and sticker messages', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::response([
            'success' => true,
            'messages' => [['id' => 'wamid.sticker_1']],
        ], 200),
    ]);

    $typing = WhatsApp::sendTypingIndicator('wamid.incoming_99');
    expect($typing->successful())->toBeTrue();

    $sticker = WhatsApp::to('94771234567')->sendSticker('https://example.com/welcome.webp');
    expect($sticker->messageId())->toBe('wamid.sticker_1');

    Http::assertSent(fn ($request) => ($request['typing_indicator']['type'] ?? null) === 'text');
    Http::assertSent(fn ($request) => ($request['type'] ?? null) === 'sticker' && ($request['sticker']['link'] ?? null) === 'https://example.com/welcome.webp');
});
