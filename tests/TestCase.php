<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Tests;

use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\WhatsAppServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsAppServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'WhatsApp' => WhatsApp::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('whatsapp.access_token', 'test-access-token');
        $app['config']->set('whatsapp.phone_number_id', '123456789012345');
        $app['config']->set('whatsapp.business_account_id', '987654321098765');
        $app['config']->set('whatsapp.api_version', 'v21.0');
        $app['config']->set('whatsapp.http.retry_times', 3);
        $app['config']->set('whatsapp.http.retry_sleep_ms', 0);
        $app['config']->set('whatsapp.webhook.verify_token', 'test-verify-token');
        $app['config']->set('whatsapp.webhook.app_secret', 'test-app-secret');
        $app['config']->set('whatsapp.webhook.verify_signature', true);
        $app['config']->set('whatsapp.webhook.idempotency.enabled', true);
    }
}
