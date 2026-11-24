<?php

declare(strict_types=1);

use LankaDev\WhatsApp\Client\WhatsAppConfig;

it('redacts sensitive tokens and secrets from debug info and safe arrays', function (): void {
    $config = new WhatsAppConfig(
        accessToken: 'EAAG-super-secret-token',
        phoneNumberId: '1234567890',
        businessAccountId: '9876543210',
        appSecret: 'my-app-secret',
        verifyToken: 'my-verify-token',
    );

    $debug = $config->__debugInfo();
    $safe = $config->toSafeArray();

    expect($debug['accessToken'])->toBe('[REDACTED]')
        ->and($debug['appSecret'])->toBe('[REDACTED]')
        ->and($debug['verifyToken'])->toBe('[REDACTED]')
        ->and(json_encode($safe))->not->toContain('EAAG-super-secret-token')
        ->and(json_encode($safe))->not->toContain('my-app-secret');
});

it('builds versioned Meta Graph API endpoints accurately', function (): void {
    $config = new WhatsAppConfig(
        accessToken: 'token',
        phoneNumberId: '111222333',
        businessAccountId: '444555666',
        apiVersion: 'v22.0',
    );

    expect($config->messagesEndpoint())->toBe('https://graph.facebook.com/v22.0/111222333/messages')
        ->and($config->mediaEndpoint())->toBe('https://graph.facebook.com/v22.0/111222333/media')
        ->and($config->templatesEndpoint())->toBe('https://graph.facebook.com/v22.0/444555666/message_templates');
});
