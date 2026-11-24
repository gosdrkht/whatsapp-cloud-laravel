<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Console;

use Illuminate\Console\Command;
use LankaDev\WhatsApp\Exceptions\WhatsAppException;
use LankaDev\WhatsApp\WhatsAppManager;

class WhatsAppTemplatesCommand extends Command
{
    protected $signature = 'whatsapp:templates
                            {--status= : Filter by template status (APPROVED, PENDING, REJECTED)}
                            {--limit=25 : Maximum number of templates to retrieve}';

    protected $description = 'List WhatsApp Business Account message templates';

    public function handle(WhatsAppManager $whatsapp): int
    {
        $filters = [
            'limit' => (int) $this->option('limit'),
        ];

        $status = $this->option('status');
        if (is_string($status) && $status !== '') {
            $filters['status'] = strtoupper($status);
        }

        try {
            $response = $whatsapp->templates()->list($filters);
            $templates = $response->json('data', []);

            if (! is_array($templates) || $templates === []) {
                $this->warn('No message templates found.');

                return self::SUCCESS;
            }

            $rows = array_map(static fn (array $t): array => [
                (string) ($t['name'] ?? ''),
                (string) ($t['language'] ?? ''),
                (string) ($t['category'] ?? ''),
                (string) ($t['status'] ?? ''),
                (string) ($t['id'] ?? ''),
            ], $templates);

            $this->table(['Name', 'Language', 'Category', 'Status', 'ID'], $rows);

            return self::SUCCESS;
        } catch (WhatsAppException $e) {
            $this->error("Failed to fetch templates: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
