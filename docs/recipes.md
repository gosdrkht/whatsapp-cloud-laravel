# Real-World Recipes

Practical examples built for POS systems, hospital management systems (HMS), ERP platforms, e-commerce stores, and SaaS applications.

---

## 1. POS Invoice PDF Receipt

Set `WHATSAPP_DEFAULT_COUNTRY_CODE=94` in `.env` so local numbers like `0771234567` work automatically:

```php
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Templates\TemplateComponent;

WhatsApp::to($sale->customer_phone)
    ->queue('invoices')
    ->sendTemplate(
        name: 'pos_invoice_receipt',
        language: 'en',
        variables: [
            'customer' => $sale->customer_name,
            'invoice_no' => $sale->invoice_number,
            'total' => 'LKR ' . number_format($sale->total, 2),
        ],
        components: [
            TemplateComponent::headerDocument($sale->pdf_url, "{$sale->invoice_number}.pdf"),
        ],
    );
```

---

## 2. Hospital Appointment Reminder (Interactive Buttons)

```php
use LankaDev\WhatsApp\Facades\WhatsApp;

WhatsApp::to($appointment->patient->phone)->sendInteractiveButtons(
    body: "Hello {$appointment->patient->name}, reminder for your appointment with Dr. {$appointment->doctor->name} tomorrow at {$appointment->time}.",
    buttons: [
        "confirm_{$appointment->id}" => 'Confirm',
        "reschedule_{$appointment->id}" => 'Reschedule',
    ],
    header: 'Clinic Appointment Reminder',
    footer: 'Colombo Care Hospital',
);
```

Handle the patient's tap via the `MessageReceived` event:

```php
use Illuminate\Support\Facades\Event;
use LankaDev\WhatsApp\Events\MessageReceived;

Event::listen(MessageReceived::class, function (MessageReceived $event): void {
    $replyId = $event->message->interactiveReplyId();

    if ($replyId && str_starts_with($replyId, 'confirm_')) {
        // Confirm appointment in database...
    }
});
```

---

## 3. OTP Login Authentication

```php
use LankaDev\WhatsApp\Facades\WhatsApp;

WhatsApp::to('0771234567')->sendOtp(
    templateName: 'auth_otp_login',
    code: '482910',
    language: 'en',
);
```

---

## 4. Payment Link (PayHere / WebXPay CTA URL)

```php
use LankaDev\WhatsApp\Facades\WhatsApp;

WhatsApp::to($order->customer_phone)->sendInteractiveCtaUrl(
    body: "Your order {$order->code} (LKR 8,500.00) is awaiting payment.",
    displayText: 'Pay Now',
    url: route('payments.checkout', $order),
    header: 'Complete Your Payment',
    footer: 'Secure online checkout',
);
```

---

## 5. Multi-Tenant SaaS (Per-Branch / Per-Clinic Credentials)

Bind `TenantCredentialResolver` in `AppServiceProvider::register()`:

```php
use LankaDev\WhatsApp\Client\WhatsAppConfig;
use LankaDev\WhatsApp\Contracts\TenantCredentialResolver;

$this->app->bind(TenantCredentialResolver::class, fn () => new class implements TenantCredentialResolver {
    public function resolve(mixed $tenant): WhatsAppConfig
    {
        return new WhatsAppConfig(
            accessToken: $tenant->whatsapp_access_token,
            phoneNumberId: $tenant->whatsapp_phone_number_id,
            businessAccountId: $tenant->whatsapp_waba_id,
        );
    }
});
```

Send from any tenant's WhatsApp Business number:

```php
WhatsApp::forTenant($clinic)
    ->to($patient->phone)
    ->sendText('Your lab report is ready for pickup.');
```

---

## 6. Rate-Paced Bulk Broadcast

```php
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Messages\MessagePayloadBuilder;

WhatsApp::broadcast(
    recipients: $subscribers->pluck('phone'),
    payloadFactory: fn (string $to): array => MessagePayloadBuilder::template($to, 'avurudu_offer', 'en'),
    batchName: 'avurudu-campaign',
    queue: 'whatsapp-bulk',
    rateLimitPerSecond: 20,
)->dispatch();
```
