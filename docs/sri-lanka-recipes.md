# Sri Lankan Developer Recipes & Tri-Lingual Templates

Practical patterns built for Sri Lankan **POS systems, e-commerce stores (COD & PayHere/WebXPay/Koko), hospital/e-channelling platforms, tuition/LMS apps, and multi-branch SaaS**.

---

## 1. Validate Sri Lankan Mobile Numbers in FormRequests

Prevent invalid or landline numbers (`011...`, `081...`) from hitting Meta's API using the built-in `SriLankanMobileNumber` rule:

```php
use LankaDev\WhatsApp\Rules\SriLankanMobileNumber;

$validated = $request->validate([
    'phone' => ['required', new SriLankanMobileNumber],
]);
```

Accepted formats: `0771234567`, `+94 77 123 4567`, `94771234567`, `771234567` (prefixes `070, 071, 072, 074, 075, 076, 077, 078`).  
Rejected: landlines (`0112345678`, `0812223344`), invalid lengths.

---

## 2. E-Commerce COD Order Verification (Reduce Fake COD Returns)

Send an interactive confirmation right after a Cash-on-Delivery checkout:

```php
use LankaDev\WhatsApp\Facades\WhatsApp;

WhatsApp::to($order->phone)->sendInteractiveButtons(
    body: "Hi {$order->customer_name}, please confirm your COD order #{$order->code} for LKR " . number_format($order->total, 2) . '.',
    buttons: [
        "cod_confirm_{$order->id}" => 'Confirm COD Order',
        "cod_cancel_{$order->id}" => 'Cancel Order',
    ],
    header: 'Order Verification',
    footer: 'Colombo Express Store',
);
```

Handle the customer's tap via webhook:

```php
use Illuminate\Support\Facades\Event;
use LankaDev\WhatsApp\Events\MessageReceived;

Event::listen(MessageReceived::class, function (MessageReceived $event): void {
    $replyId = $event->message->interactiveReplyId();

    if ($replyId && str_starts_with($replyId, 'cod_confirm_')) {
        $orderId = (int) str_replace('cod_confirm_', '', $replyId);
        // Mark order as COD-confirmed and dispatch to courier (Koombiyo / PromptX / Domex)
    }
});
```

---

## 3. POS Bill PDF Generation (DomPDF → Upload → Send)

```php
use Barryvdh\DomPDF\Facade\Pdf;
use LankaDev\WhatsApp\Facades\WhatsApp;

$path = storage_path("app/tmp/INV-{$sale->id}.pdf");
Pdf::loadView('pdf.pos-receipt', ['sale' => $sale])->save($path);

$mediaId = WhatsApp::media()->upload($path, 'application/pdf')->json('id');

WhatsApp::to($sale->customer_phone)->sendDocument(
    $mediaId,
    "Invoice-{$sale->invoice_number}.pdf",
    "Thank you for shopping with us! Total: LKR " . number_format($sale->total, 2),
);
```

---

## 4. `LKR` Currency & `Asia/Colombo` Date/Time Template Parameters

```php
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Templates\TemplateComponent;

WhatsApp::to('0771234567')->sendTemplate(
    name: 'payment_confirmation',
    language: 'en',
    components: [
        TemplateComponent::body([
            TemplateComponent::textParameter('Kamal Perera'),
            TemplateComponent::lkr(12500.50),
            TemplateComponent::colomboDateTime(now(), 'Y-m-d h:i A'),
        ]),
    ],
);
```

---

## 5. WhatsApp → Local SMS Fallback (Notify.lk / Text.lk / ShoutOUT)

If a recipient is not on WhatsApp (e.g. Meta error `131026`), automatically fall back to your local SMS gateway using the `MessageFailed` event:

```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use LankaDev\WhatsApp\Events\MessageFailed;
use LankaDev\WhatsApp\Models\WhatsAppMessageLog;

Event::listen(MessageFailed::class, function (MessageFailed $event): void {
    $log = WhatsAppMessageLog::query()->where('wamid', $event->status->id)->first();
    if (! $log) {
        return;
    }

    // Example: Notify.lk SMS fallback
    Http::post('https://app.notify.lk/api/v1/send', [
        'user_id' => config('services.notify_lk.user_id'),
        'api_key' => config('services.notify_lk.api_key'),
        'sender_id' => config('services.notify_lk.sender_id'),
        'to' => $event->status->recipientId,
        'message' => "Notification from MyApp (fallback for WhatsApp #{$event->status->id}).",
    ]);
});
```

---

## 6. Tri-Lingual Template Blueprints (`en`, `si`, `ta`)

Create the same template name in Meta Business Manager across English (`en`), Sinhala (`si`), and Tamil (`ta`), then route using `$user->locale`:

```php
$language = in_array($patient->locale, ['en', 'si', 'ta'], true) ? $patient->locale : 'en';

WhatsApp::to($patient->phone)->sendTemplate(
    name: 'hospital_appointment_reminder',
    language: $language,
    variables: [
        'patient' => $patient->name,
        'doctor' => $appointment->doctor_name,
        'time' => $appointment->starts_at->timezone('Asia/Colombo')->format('Y-m-d h:i A'),
    ],
);
```

### Sample Tri-Lingual Body Copy

- **English (`en`)**:
  `Hello {{1}}, this is a reminder for your appointment with Dr. {{2}} on {{3}}.`
- **Sinhala (`si`)**:
  `ආයුබෝවන් {{1}}, වෛද්‍ය {{2}} සමඟ ඔබගේ හමුවීම {{3}} දිනට නියමිත බව මතක් කර සිටිමු.`
- **Tamil (`ta`)**:
  `வணக்கம் {{1}}, மருத்துவர் {{2}} உடனான உங்கள் சந்திப்பு {{3}} அன்று திட்டமிடப்பட்டுள்ளது என்பதை நினைவூட்டுகிறோம்.`

---

## 7. Scaffold a WhatsApp Notification via Artisan

```bash
php artisan whatsapp:make-notification OrderConfirmationNotification --template=order_confirmation
```
