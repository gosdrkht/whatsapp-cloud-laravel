<?php

declare(strict_types=1);

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Notifications\WhatsAppChannel;
use LankaDev\WhatsApp\Notifications\WhatsAppMessage;

it('delivers Laravel notifications through the whatsapp channel', function (): void {
    $fake = WhatsApp::fake();

    $user = new class
    {
        use Notifiable;

        public string $name = 'Kamal';

        public function routeNotificationForWhatsApp(): string
        {
            return '94771234567';
        }
    };

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['whatsapp'];
        }

        public function toWhatsApp(object $notifiable): WhatsAppMessage
        {
            return WhatsAppMessage::template('order_confirmation', 'en', [
                'customer' => $notifiable->name,
                'order_id' => 'ORD-1001',
            ]);
        }
    };

    $user->notify($notification);

    $fake->assertSentCount(1);
    $fake->assertSentTemplate('94771234567', 'order_confirmation', 'en');
});

it('supports WhatsAppChannel::class, plain string messages, and attribute fallback', function (): void {
    $fake = WhatsApp::fake();

    $patient = new class
    {
        use Notifiable;

        public string $whatsapp_number = '94719876543';
    };

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return [WhatsAppChannel::class];
        }

        public function toWhatsApp(object $notifiable): string
        {
            return 'Your clinic appointment is confirmed for tomorrow at 9:00 AM.';
        }
    };

    $patient->notify($notification);

    $fake->assertSentText('94719876543', 'clinic appointment is confirmed');
});
