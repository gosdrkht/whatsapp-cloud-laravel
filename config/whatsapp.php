<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Business Cloud API Credentials
    |--------------------------------------------------------------------------
    |
    | Configure your Meta Cloud API access token, phone number ID, and WhatsApp
    | Business Account (WABA) ID. Never commit real credentials to version
    | control or expose access tokens in application logs.
    |
    */

    'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),

    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Graph API Base URL & Version
    |--------------------------------------------------------------------------
    |
    | Specify the Meta Graph API version to target (e.g. v21.0, v22.0).
    |
    */

    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client & Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configure request timeout and automatic retry behavior for transient
    | errors such as rate limiting (429) or temporary 5xx server failures.
    |
    */

    'http' => [
        'timeout' => (int) env('WHATSAPP_HTTP_TIMEOUT', 30),
        'connect_timeout' => (int) env('WHATSAPP_HTTP_CONNECT_TIMEOUT', 10),
        'retry_times' => (int) env('WHATSAPP_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('WHATSAPP_RETRY_SLEEP_MS', 250),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the webhook route, verification token, app secret for HMAC
    | SHA-256 signature verification against the raw request body, and
    | idempotency caching to prevent duplicate event processing.
    |
    */

    'webhook' => [
        'enabled' => env('WHATSAPP_WEBHOOK_ENABLED', true),
        'path' => env('WHATSAPP_WEBHOOK_PATH', 'whatsapp/webhook'),
        'middleware' => ['api'],
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', ''),
        'app_secret' => env('WHATSAPP_APP_SECRET', ''),
        'verify_signature' => env('WHATSAPP_VERIFY_SIGNATURE', true),
        'idempotency' => [
            'enabled' => env('WHATSAPP_WEBHOOK_IDEMPOTENCY', true),
            'store' => env('WHATSAPP_WEBHOOK_CACHE_STORE'),
            'ttl' => (int) env('WHATSAPP_WEBHOOK_IDEMPOTENCY_TTL', 86400),
            'prefix' => 'whatsapp_webhook:',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Recommended for bulk messaging and notification dispatching.
    |
    */

    'queue' => [
        'connection' => env('WHATSAPP_QUEUE_CONNECTION'),
        'name' => env('WHATSAPP_QUEUE_NAME', 'default'),
        'tries' => (int) env('WHATSAPP_QUEUE_TRIES', 3),
        'backoff' => [10, 60, 300],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, outgoing API requests and errors are logged to the
    | configured channel with sensitive tokens automatically redacted.
    |
    */

    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING_ENABLED', false),
        'channel' => env('WHATSAPP_LOG_CHANNEL'),
    ],

];
