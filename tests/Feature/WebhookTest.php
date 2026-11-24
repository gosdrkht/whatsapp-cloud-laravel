<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LankaDev\WhatsApp\Events\MessageDelivered;
use LankaDev\WhatsApp\Events\MessageFailed;
use LankaDev\WhatsApp\Events\MessageRead;
use LankaDev\WhatsApp\Events\MessageReceived;
use LankaDev\WhatsApp\Events\MessageStatusUpdated;
use LankaDev\WhatsApp\Events\WebhookReceived;
use LankaDev\WhatsApp\Testing\WebhookFactory;

it('verifies Meta webhook subscription challenge on GET', function (): void {
    $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=test-verify-token&hub.challenge=challenge-12345')
        ->assertOk()
        ->assertSeeText('challenge-12345');

    $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=wrong-token&hub.challenge=challenge-12345')
        ->assertForbidden();
});

it('rejects POST webhooks with missing or invalid HMAC-SHA256 signatures', function (): void {
    $payload = WebhookFactory::incomingText();

    $this->postJson('/whatsapp/webhook', $payload)
        ->assertForbidden();

    $this->postJson('/whatsapp/webhook', $payload, [
        'X-Hub-Signature-256' => 'sha256=deadbeef',
    ])->assertForbidden();
});

it('dispatches MessageReceived and suppresses duplicate deliveries via idempotency', function (): void {
    Event::fake();

    $payload = WebhookFactory::incomingText(
        from: '94771234567',
        body: 'Do you deliver to Kandy?',
        messageId: 'wamid.unique_msg_001',
        contactName: 'Nimal',
    );
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
    )->assertOk()->assertJsonPath('summary.messages_dispatched', 1);

    // Send exact duplicate delivery — idempotency store must skip it
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
    )->assertOk()
        ->assertJsonPath('summary.messages_dispatched', 0)
        ->assertJsonPath('summary.duplicates_skipped', 1);

    Event::assertDispatched(WebhookReceived::class, 2);
    Event::assertDispatched(MessageReceived::class, 1);
    Event::assertDispatched(MessageReceived::class, function (MessageReceived $event): bool {
        return $event->message->from === '94771234567'
            && $event->message->text() === 'Do you deliver to Kandy?'
            && $event->message->contactName === 'Nimal';
    });
});

it('dispatches delivered, read, and failed status events', function (): void {
    Event::fake();

    $statuses = [
        WebhookFactory::statusUpdate('delivered', 'wamid.st_1'),
        WebhookFactory::statusUpdate('read', 'wamid.st_1'),
        WebhookFactory::statusUpdate('failed', 'wamid.st_2', '94771234567', [
            ['code' => 131047, 'title' => 'Re-engagement message', 'message' => 'More than 24 hours have passed.'],
        ]),
    ];

    foreach ($statuses as $payload) {
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
    }

    Event::assertDispatched(MessageStatusUpdated::class, 3);
    Event::assertDispatched(MessageDelivered::class, 1);
    Event::assertDispatched(MessageRead::class, 1);
    Event::assertDispatched(MessageFailed::class, function (MessageFailed $event): bool {
        return $event->status->firstErrorCode() === 131047
            && str_contains((string) $event->status->firstErrorMessage(), '24 hours');
    });
});
