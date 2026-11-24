<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Console;

use Illuminate\Console\Command;

class WhatsAppWebhookCommand extends Command
{
    protected $signature = 'whatsapp:webhook';

    protected $description = 'Display WhatsApp webhook endpoint details and configuration status';

    public function handle(): int
    {
        $enabled = (bool) config('whatsapp.webhook.enabled', true);
        $path = trim((string) config('whatsapp.webhook.path', 'whatsapp/webhook'), '/');
        $url = url($path);

        $this->table(
            ['Setting', 'Value'],
            [
                ['Webhook Enabled', $enabled ? 'Yes' : 'No'],
                ['Callback URL', $url],
                ['API Version', (string) config('whatsapp.api_version', 'v21.0')],
                ['Phone Number ID', config('whatsapp.phone_number_id') ? 'Configured' : 'Missing'],
                ['Access Token', config('whatsapp.access_token') ? 'Configured (hidden)' : 'Missing'],
                ['Verify Token', config('whatsapp.webhook.verify_token') ? 'Configured (hidden)' : 'Missing'],
                ['App Secret', config('whatsapp.webhook.app_secret') ? 'Configured (hidden)' : 'Missing'],
                ['Signature Verification', config('whatsapp.webhook.verify_signature', true) ? 'Enabled' : 'Disabled'],
                ['Idempotency Protection', config('whatsapp.webhook.idempotency.enabled', true) ? 'Enabled' : 'Disabled'],
            ],
        );

        return self::SUCCESS;
    }
}
