<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Console;

use Illuminate\Console\Command;
use LankaDev\WhatsApp\Exceptions\WhatsAppException;
use LankaDev\WhatsApp\WhatsAppManager;

class WhatsAppTestCommand extends Command
{
    protected $signature = 'whatsapp:test
                            {to : Recipient phone number in E.164 format (e.g. 94771234567)}
                            {--message=Hello from WhatsApp Cloud Laravel! : Text message body to send}';

    protected $description = 'Send a test WhatsApp text message to verify your Meta Cloud API configuration';

    public function handle(WhatsAppManager $whatsapp): int
    {
        $to = (string) $this->argument('to');
        $message = (string) $this->option('message');

        $this->info("Sending test WhatsApp message to {$to}...");

        try {
            $response = $whatsapp->to($to)->sendText($message);
            $messageId = $response->messageId() ?? 'N/A';

            $this->info("Message sent successfully! Message ID: {$messageId}");

            return self::SUCCESS;
        } catch (WhatsAppException $e) {
            $this->error("Failed to send message: {$e->getMessage()}");

            if ($e->errorCode !== null) {
                $this->line("Meta Error Code: {$e->errorCode}");
            }

            if ($e->fbTraceId !== null) {
                $this->line("FB Trace ID: {$e->fbTraceId}");
            }

            return self::FAILURE;
        }
    }
}
