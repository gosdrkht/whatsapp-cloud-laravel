<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use LankaDev\WhatsApp\Exceptions\AuthenticationException;
use LankaDev\WhatsApp\Exceptions\RateLimitException;
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Jobs\SendWhatsAppMessageJob;

it('sends text, template, and document messages via Meta Graph API', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::response([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '94771234567', 'wa_id' => '94771234567']],
            'messages' => [['id' => 'wamid.test123']],
        ], 200),
    ]);

    $response = WhatsApp::to('94771234567')->sendText('Hello from Sri Lanka!');

    expect($response->successful())->toBeTrue()
        ->and($response->messageId())->toBe('wamid.test123');

    WhatsApp::to('94771234567')->sendTemplate('order_confirmation', 'en', [
        'customer' => 'Kamal',
        'order_id' => 'ORD-1001',
    ]);

    WhatsApp::to('94771234567')->sendDocument(
        'https://example.com/invoice.pdf',
        'Invoice-1001.pdf',
    );

    Http::assertSentCount(3);
});

it('automatically retries transient 5xx and 429 errors', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::sequence()
            ->push(['error' => ['message' => 'Temporary overload', 'code' => 130429]], 429)
            ->push(['error' => ['message' => 'Internal error']], 500)
            ->push([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.after_retry']],
            ], 200),
    ]);

    $response = WhatsApp::to('94771234567')->sendText('Retry test');

    expect($response->messageId())->toBe('wamid.after_retry');
    Http::assertSentCount(3);
});

it('throws typed AuthenticationException on 401 Meta errors', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::response([
            'error' => [
                'message' => 'Invalid OAuth access token.',
                'type' => 'OAuthException',
                'code' => 190,
                'fbtrace_id' => 'trace-123',
            ],
        ], 401),
    ]);

    expect(fn () => WhatsApp::to('94771234567')->sendText('Fail auth'))
        ->toThrow(AuthenticationException::class, 'Invalid OAuth access token.');
});

it('throws typed RateLimitException on 429 Meta errors after retries exhaust', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/messages' => Http::response([
            'error' => ['message' => 'Rate limit hit', 'code' => 80007],
        ], 429),
    ]);

    expect(fn () => WhatsApp::to('94771234567')->sendText('Fail rate limit'))
        ->toThrow(RateLimitException::class, 'Rate limit hit');
});

it('supports WhatsApp::fake() for zero-network test assertions', function (): void {
    $fake = WhatsApp::fake();

    WhatsApp::to('94771234567')->sendText('Ayubowan!');
    WhatsApp::to('94771234567')->sendTemplate('order_confirmation', 'en', ['customer' => 'Kamal']);
    WhatsApp::to('94771234567')->sendDocument('https://example.com/invoice.pdf', 'Invoice-1001.pdf');
    WhatsApp::markAsRead('wamid.incoming1');

    $fake->assertSentCount(3);
    $fake->assertSentTo('94771234567');
    $fake->assertSentText('94771234567', 'Ayubowan');
    $fake->assertSentTemplate('94771234567', 'order_confirmation', 'en');
    $fake->assertSentDocument('94771234567', 'Invoice-1001.pdf');
    $fake->assertMarkedAsRead('wamid.incoming1');
});

it('dispatches SendWhatsAppMessageJob when queue() is chained', function (): void {
    Queue::fake();

    $response = WhatsApp::to('94771234567')
        ->queue('whatsapp-bulk')
        ->sendText('Queued promotion');

    expect($response->status())->toBe(202)
        ->and($response->json('queued'))->toBeTrue();

    Queue::assertPushedOn('whatsapp-bulk', SendWhatsAppMessageJob::class, function (SendWhatsAppMessageJob $job): bool {
        return $job->payload['to'] === '94771234567'
            && $job->payload['text']['body'] === 'Queued promotion';
    });
});
