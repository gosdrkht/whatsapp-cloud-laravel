<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use LankaDev\WhatsApp\Rules\SriLankanMobileNumber;
use LankaDev\WhatsApp\Rules\WhatsAppPhoneNumber;
use LankaDev\WhatsApp\Templates\TemplateComponent;

it('validates Sri Lankan mobile numbers and rejects landlines', function (): void {
    $valid = ['0771234567', '+94 71 234 5678', '94761234567', '701234567', '0741234567', '0781234567'];

    foreach ($valid as $phone) {
        $validator = Validator::make(['phone' => $phone], ['phone' => [new SriLankanMobileNumber]]);
        expect($validator->passes())->toBeTrue("Expected {$phone} to be valid");
    }

    $invalid = ['0112345678', '0812223344', '+447700900077', '0791234567', '12345'];

    foreach ($invalid as $phone) {
        $validator = Validator::make(['phone' => $phone], ['phone' => [new SriLankanMobileNumber]]);
        expect($validator->fails())->toBeTrue("Expected {$phone} to fail");
    }
});

it('validates general E.164 WhatsApp phone numbers', function (): void {
    expect(Validator::make(['phone' => '+94771234567'], ['phone' => [new WhatsAppPhoneNumber]])->passes())->toBeTrue()
        ->and(Validator::make(['phone' => '0771234567'], ['phone' => [new WhatsAppPhoneNumber('94')]])->passes())->toBeTrue()
        ->and(Validator::make(['phone' => 'bad'], ['phone' => [new WhatsAppPhoneNumber]])->fails())->toBeTrue();
});

it('formats LKR currency and Asia/Colombo date_time template parameters', function (): void {
    $lkr = TemplateComponent::lkr(12500.5);
    expect($lkr)->toBe([
        'type' => 'currency',
        'currency' => [
            'fallback_value' => 'LKR 12,500.50',
            'code' => 'LKR',
            'amount_1000' => 12500500,
        ],
    ]);

    $utc = new DateTimeImmutable('2026-11-24 03:30:00', new DateTimeZone('UTC'));
    $colombo = TemplateComponent::colomboDateTime($utc, 'Y-m-d H:i');
    expect($colombo)->toBe([
        'type' => 'date_time',
        'date_time' => [
            'fallback_value' => '2026-11-24 09:00',
        ],
    ]);
});

it('scaffolds a WhatsApp notification via whatsapp:make-notification', function (): void {
    $path = app_path('Notifications/OrderConfirmationNotification.php');
    File::delete($path);

    try {
        $this->artisan('whatsapp:make-notification', [
            'name' => 'OrderConfirmationNotification',
            '--template' => 'order_confirmation',
        ])
            ->expectsOutputToContain('WhatsApp notification created successfully')
            ->assertSuccessful();

        expect(File::exists($path))->toBeTrue();
        $contents = File::get($path);
        expect($contents)
            ->toContain('class OrderConfirmationNotification extends Notification implements ShouldQueue')
            ->toContain("WhatsAppMessage::template('order_confirmation'");
    } finally {
        File::delete($path);
    }
});
