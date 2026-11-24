<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Support;

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use LankaDev\WhatsApp\Jobs\SendWhatsAppMessageJob;

/**
 * Builds rate-paced Laravel Bus Batches for bulk WhatsApp broadcasts.
 */
final class BroadcastBatch
{
    /**
     * Create a PendingBatch of SendWhatsAppMessageJob instances.
     *
     * @param  iterable<int, string>  $recipients
     * @param  callable(string): array<string, mixed>  $payloadFactory  Receives normalized phone number and returns a Meta message payload
     */
    public static function create(
        iterable $recipients,
        callable $payloadFactory,
        ?string $batchName = null,
        ?string $queue = null,
        ?string $connection = null,
        int $rateLimitPerSecond = 0,
        ?string $phoneNumberId = null,
    ): PendingBatch {
        $jobs = [];
        $index = 0;

        foreach ($recipients as $recipient) {
            $normalized = PhoneNumberNormalizer::normalize((string) $recipient);
            $payload = $payloadFactory($normalized);
            $payload['to'] ??= $normalized;

            $job = new SendWhatsAppMessageJob($payload, $phoneNumberId);

            if ($queue !== null && $queue !== '') {
                $job->onQueue($queue);
            }

            if ($connection !== null && $connection !== '') {
                $job->onConnection($connection);
            }

            if ($rateLimitPerSecond > 0) {
                $delaySeconds = intdiv($index, $rateLimitPerSecond);
                if ($delaySeconds > 0) {
                    $job->delay($delaySeconds);
                }
            }

            $jobs[] = $job;
            $index++;
        }

        $batch = Bus::batch($jobs)->name($batchName ?? 'whatsapp-broadcast');

        if ($queue !== null && $queue !== '') {
            $batch->onQueue($queue);
        }

        if ($connection !== null && $connection !== '') {
            $batch->onConnection($connection);
        }

        return $batch;
    }
}
