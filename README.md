# WhatsApp Cloud Laravel

**The simplest way to integrate WhatsApp Business Cloud API into Laravel applications.**

Simple. Powerful. Developer-friendly.  
An elegant, production-ready PHP & Laravel SDK for the official **Meta WhatsApp Business Cloud API**.

> Open-source community project by **LankaDev / Leading Edge Solutions**. Not an official Meta product or endorsement.

---

## Features

- **Message Sending Engine**: Text, images, videos, documents, audio, locations, contact cards, reactions, interactive reply buttons, interactive lists, and template messages.
- **Laravel Notifications Channel**: Send WhatsApp messages using Laravel's built-in `$user->notify(...)` system.
- **Webhook Management**: Automatic `GET` verification, raw-body `X-Hub-Signature-256` HMAC verification, cache-backed idempotency protection, and strongly-typed Laravel events (`MessageReceived`, `MessageDelivered`, `MessageRead`, `MessageFailed`, `MessageStatusUpdated`, `WebhookReceived`).
- **Template Management**: Retrieve templates, check approval statuses, create/delete templates, build header/body/button components, and validate parameters locally before dispatch.
- **Media Management**: Upload, inspect, download, and delete media with automatic MIME type and Meta file-size validation.
- **Queues & Bulk Messaging**: Chain `->queue()` on any message to dispatch via Laravel queues with configurable retries and backoff.
- **Production Developer Experience**: Typed exceptions (`AuthenticationException`, `RateLimitException`, `ValidationException`, `MediaException`), immutable `ApiResponse` DTOs, exponential-backoff retries for transient 429/5xx errors, redacted logging, Artisan CLI tools, and `WhatsApp::fake()` for testing.
- **Framework-Independent Core**: Use `LankaDev\WhatsApp\Client\WhatsAppClient` directly in any PHP 8.2+ project outside Laravel.

---

## Requirements

- PHP **8.2+**
- Laravel **11, 12, or 13**

---

## Installation

```bash
composer require lankadev/whatsapp-cloud-laravel
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="whatsapp-config"
```

---

## Configuration

Add your Meta WhatsApp Business Cloud API credentials to `.env`:

```env
WHATSAPP_ACCESS_TOKEN=your_permanent_or_system_user_token
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_BUSINESS_ACCOUNT_ID=your_waba_id
WHATSAPP_APP_SECRET=your_meta_app_secret
WHATSAPP_VERIFY_TOKEN=your_webhook_verify_token
WHATSAPP_API_VERSION=v21.0
```

---

## Quick Start

### Send a Text Message

```php
use LankaDev\WhatsApp\Facades\WhatsApp;

$response = WhatsApp::to('94771234567')
    ->sendText('Hello from Sri Lanka!');

$messageId = $response->messageId();
```

### Send a Template Message

```php
WhatsApp::to('94771234567')->sendTemplate(
    'order_confirmation',
    'en',
    [
        'customer' => 'Kamal',
        'order_id' => 'ORD-1001',
    ]
);
```

With header media and buttons using `TemplateComponent`:

```php
use LankaDev\WhatsApp\Templates\TemplateComponent;

WhatsApp::to('94771234567')->sendTemplate(
    name: 'invoice_ready',
    language: 'en',
    variables: ['customer' => 'Kamal', 'amount' => 'LKR 12,500'],
    components: [
        TemplateComponent::headerDocument('https://example.com/invoice.pdf', 'Invoice-1001.pdf'),
        TemplateComponent::urlButton(0, 'ORD-1001'),
    ],
);
```

### Send an Invoice / Document

```php
WhatsApp::to('94771234567')->sendDocument(
    'https://example.com/invoice.pdf',
    'Invoice-1001.pdf',
    'Thank you for your purchase!'
);
```

### Send Interactive Buttons & Lists

```php
// Reply buttons (up to 3)
WhatsApp::to('94771234567')->sendInteractiveButtons(
    body: 'Please confirm your hospital appointment for tomorrow at 9:00 AM.',
    buttons: [
        'confirm_appt' => 'Confirm',
        'reschedule_appt' => 'Reschedule',
    ],
    header: 'Appointment Reminder',
    footer: 'Colombo Care Clinic',
);

// Interactive list (up to 10 rows total)
WhatsApp::to('94771234567')->sendInteractiveList(
    body: 'Choose a service to continue:',
    buttonText: 'View Services',
    sections: [
        [
            'title' => 'Support',
            'rows' => [
                ['id' => 'billing', 'title' => 'Billing & Invoices', 'description' => 'Check payments'],
                ['id' => 'delivery', 'title' => 'Track Delivery', 'description' => 'Live order status'],
            ],
        ],
    ],
);
```

### Send Media, Location, and Contact Cards

```php
use LankaDev\WhatsApp\Messages\ContactCard;

WhatsApp::to('94771234567')->sendImage('https://example.com/banner.jpg', 'New Arrival!');
WhatsApp::to('94771234567')->sendLocation(6.9271, 79.8612, 'Colombo Office', 'Galle Road, Colombo 03');

$contact = ContactCard::make('Kamal Perera', 'Kamal', 'Perera')
    ->addPhone('+94771234567', 'CELL', '94771234567')
    ->organization('Leading Edge Solutions');

WhatsApp::to('94771234567')->sendContacts([$contact]);
```

### Queue Messages (Recommended for Bulk Sends)

```php
WhatsApp::to('94771234567')
    ->queue('whatsapp')
    ->sendTemplate('monthly_statement', 'en', ['customer' => 'Kamal']);
```

---

## Laravel Notifications Channel

Use Laravel's built-in notification system to send WhatsApp notifications seamlessly:

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use LankaDev\WhatsApp\Notifications\WhatsAppMessage;

class OrderConfirmationNotification extends Notification
{
    public function __construct(public readonly object $order) {}

    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        return WhatsAppMessage::template('order_confirmation', 'en', [
            'customer' => $notifiable->name,
            'order_id' => $this->order->code,
        ]);
    }
}
```

Define the recipient route on your `User` model:

```php
public function routeNotificationForWhatsApp(): string
{
    return $this->whatsapp_number; // e.g. '94771234567'
}
```

Then notify as usual:

```php
$user->notify(new OrderConfirmationNotification($order));
```

---

## Webhooks & Events

The package automatically registers:

- `GET /whatsapp/webhook` — Meta subscription verification using `WHATSAPP_VERIFY_TOKEN`
- `POST /whatsapp/webhook` — Verified via `X-Hub-Signature-256` against the **original raw request body** and protected by cache-backed idempotency

Listen to typed events in your application:

```php
use Illuminate\Support\Facades\Event;
use LankaDev\WhatsApp\Events\MessageReceived;
use LankaDev\WhatsApp\Events\MessageFailed;

Event::listen(MessageReceived::class, function (MessageReceived $event): void {
    $from = $event->message->from;
    $text = $event->message->text();
});

Event::listen(MessageFailed::class, function (MessageFailed $event): void {
    logger()->warning('WhatsApp delivery failed', [
        'message_id' => $event->status->id,
        'error' => $event->status->firstErrorMessage(),
    ]);
});
```

---

## Template & Media Management

```php
// Templates
$templates = WhatsApp::templates()->list(['status' => 'APPROVED']);
$status = WhatsApp::templates()->getStatus('order_confirmation', 'en');

// Media
$uploaded = WhatsApp::media()->upload(storage_path('app/invoices/INV-1001.pdf'));
$mediaId = $uploaded->json('id');

$bytes = WhatsApp::media()->download($mediaId, storage_path('app/downloads/INV-1001.pdf'));
```

---

## Artisan Commands

```bash
# Verify credentials by sending a test message
php artisan whatsapp:test 94771234567 --message="Hello from Laravel!"

# List WABA templates
php artisan whatsapp:templates --status=APPROVED

# Inspect webhook URL & configuration health (secrets stay hidden)
php artisan whatsapp:webhook
```

---

## Testing

Use `WhatsApp::fake()` in Pest or PHPUnit to assert outgoing messages without making network requests:

```php
use LankaDev\WhatsApp\Facades\WhatsApp;

it('sends an order confirmation via WhatsApp', function (): void {
    $fake = WhatsApp::fake();

    // Run your application code...
    WhatsApp::to('94771234567')->sendTemplate('order_confirmation', 'en', [
        'customer' => 'Kamal',
        'order_id' => 'ORD-1001',
    ]);

    $fake->assertSentCount(1);
    $fake->assertSentTemplate('94771234567', 'order_confirmation', 'en');
});
```

---

## Standalone PHP Usage (Without Laravel)

```php
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Client\WhatsAppConfig;
use LankaDev\WhatsApp\WhatsAppManager;

$config = new WhatsAppConfig(
    accessToken: $_ENV['WHATSAPP_ACCESS_TOKEN'],
    phoneNumberId: $_ENV['WHATSAPP_PHONE_NUMBER_ID'],
    businessAccountId: $_ENV['WHATSAPP_BUSINESS_ACCOUNT_ID'],
);

$whatsapp = new WhatsAppManager(new WhatsAppClient($config));
$whatsapp->to('94771234567')->sendText('Hello from standalone PHP!');
```

---

## Security & Meta Compliance Notes

- **Token Protection**: Access tokens and app secrets are redacted from `__debugInfo()`, `toSafeArray()`, CLI output, and logs.
- **Raw Body Signature Verification**: Webhook signatures are always verified against `$request->getContent()` using constant-time `hash_equals()`.
- **Idempotency**: Duplicate webhook deliveries are deduplicated automatically via Laravel Cache.
- **Messaging Policies**: Respect Meta's opt-in requirements, 24-hour customer service window, and per-delivered-template-message pricing model.

---

## Roadmap

- **v1.0–v1.3 (Included)**: Text, media, interactive buttons & lists, contact cards, templates, media management, webhooks with signature verification & idempotency, Laravel Notifications channel, queues, events, Artisan commands, and `WhatsApp::fake()`.
- **v2.0**: Optional multi-tenant WABA/phone-number credential resolvers, analytics hooks, and WhatsApp Flows support.

---

## License

MIT License. See [LICENSE](LICENSE) for details.
