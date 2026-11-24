<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LankaDev\WhatsApp\Client\WhatsAppClient;

/**
 * Queued job for asynchronous and bulk WhatsApp Cloud API message delivery.
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    /** @var array<int, int> */
    public array $backoff;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly ?string $phoneNumberId = null,
    ) {
        $this->tries = (int) config('whatsapp.queue.tries', 3);
        $this->backoff = (array) config('whatsapp.queue.backoff', [10, 60, 300]);

        $connection = config('whatsapp.queue.connection');
        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        $queue = config('whatsapp.queue.name', 'default');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(WhatsAppClient $client): void
    {
        $client->sendMessage($this->payload, $this->phoneNumberId);
    }
}
