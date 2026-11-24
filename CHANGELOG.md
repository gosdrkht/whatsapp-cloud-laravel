# Changelog

All notable changes to `lankadev/whatsapp-cloud-laravel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-11-24

### Added
- Laravel validation rules: `SriLankanMobileNumber` (validating Sri Lankan mobile prefixes `070`–`078` and rejecting landlines) and `WhatsAppPhoneNumber`.
- `TemplateComponent::lkr()` currency helper and `TemplateComponent::colomboDateTime()` (`Asia/Colombo`) helper.
- `php artisan whatsapp:make-notification` Artisan command to scaffold queued WhatsApp notifications.
- Step-by-step Meta Cloud API setup guide (`docs/meta-setup.md`).
- Sri Lankan developer recipes & tri-lingual (`en`, `si`, `ta`) template guide (`docs/sri-lanka-recipes.md`), covering COD verification, DomPDF POS receipts, PayHere links, and local SMS fallback patterns.

## [1.1.0] - 2026-11-24

### Added
- Configurable `default_country_code` phone number normalizer (`PhoneNumberNormalizer`) supporting local formats such as `0771234567`.
- Built-in OTP / Authentication template helper (`sendOtp()` and `WhatsAppMessage::otp()`).
- Interactive Call-To-Action URL (`sendInteractiveCtaUrl()`) and WhatsApp Flows (`sendFlow()`) message support.
- Automatic 24-hour customer service window tracker (`ConversationWindow` / `WhatsApp::isWithinServiceWindow()`).
- Opt-in database message logging (`whatsapp_messages` publishable migration and `WhatsAppMessageLog` model) tracking delivery/read/failed timestamps and Meta pricing categories.
- Multi-tenant support via `WhatsApp::using(...)` and `WhatsApp::forTenant(...)` (`TenantCredentialResolver` contract).
- Rate-paced bulk broadcast batching via `WhatsApp::broadcast(...)` backed by Laravel Bus Batches.
- Typing indicator (`sendTypingIndicator()`) and sticker message (`sendSticker()`) support.
- Static analysis (`phpstan/phpstan`), code style enforcement (`laravel/pint`), community templates, and real-world recipes (`docs/recipes.md`).

## [1.0.0] - 2026-11-24

### Added
- Initial production release of `lankadev/whatsapp-cloud-laravel` for PHP 8.2+ and Laravel 11, 12, and 13.
- Message Sending Engine (text, image, video, document, audio, location, contact cards, reactions, interactive reply buttons & lists, templates).
- Laravel Notifications Channel (`WhatsAppChannel` & `WhatsAppMessage`).
- Webhook verification (`GET`), raw-body `X-Hub-Signature-256` verification, cache-backed idempotency, and typed Laravel events.
- Template Management (`TemplateManager`, `TemplateComponent`, `TemplateValidator`) and Media Management (`MediaManager`, `MimeTypeValidator`).
- Queue support (`SendWhatsAppMessageJob`), Artisan commands (`whatsapp:test`, `whatsapp:templates`, `whatsapp:webhook`), and `WhatsApp::fake()` testing utilities.
