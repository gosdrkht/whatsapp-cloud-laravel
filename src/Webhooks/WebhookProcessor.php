<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

use Illuminate\Contracts\Events\Dispatcher;
use LankaDev\WhatsApp\Events\MessageDelivered;
use LankaDev\WhatsApp\Events\MessageFailed;
use LankaDev\WhatsApp\Events\MessageRead;
use LankaDev\WhatsApp\Events\MessageReceived;
use LankaDev\WhatsApp\Events\MessageStatusUpdated;
use LankaDev\WhatsApp\Events\WebhookReceived;

/**
 * Parses incoming Meta WhatsApp webhook payloads, enforces idempotency,
 * and dispatches typed Laravel events.
 */
class WebhookProcessor
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly WebhookIdempotencyStore $idempotency,
    ) {}

    /**
     * Process a decoded Meta webhook JSON payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{messages_dispatched: int, statuses_dispatched: int, duplicates_skipped: int}
     */
    public function process(array $payload): array
    {
        $this->events->dispatch(new WebhookReceived($payload));

        $messagesDispatched = 0;
        $statusesDispatched = 0;
        $duplicatesSkipped = 0;

        $entries = $payload['entry'] ?? [];
        if (! is_array($entries)) {
            return [
                'messages_dispatched' => 0,
                'statuses_dispatched' => 0,
                'duplicates_skipped' => 0,
            ];
        }

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                if (! is_array($value)) {
                    continue;
                }

                $metadata = is_array($value['metadata'] ?? null) ? $value['metadata'] : [];
                $contactsByWaId = $this->indexContacts($value['contacts'] ?? []);

                foreach ($value['messages'] ?? [] as $rawMessage) {
                    if (! is_array($rawMessage)) {
                        continue;
                    }

                    $messageId = (string) ($rawMessage['id'] ?? '');
                    if ($messageId !== '' && ! $this->idempotency->acquire("msg:{$messageId}")) {
                        $duplicatesSkipped++;

                        continue;
                    }

                    $from = (string) ($rawMessage['from'] ?? '');
                    $message = IncomingMessage::fromWebhook(
                        $rawMessage,
                        $metadata,
                        $contactsByWaId[$from] ?? null,
                    );

                    $this->events->dispatch(new MessageReceived($message));
                    $messagesDispatched++;
                }

                foreach ($value['statuses'] ?? [] as $rawStatus) {
                    if (! is_array($rawStatus)) {
                        continue;
                    }

                    $statusId = (string) ($rawStatus['id'] ?? '');
                    $statusName = (string) ($rawStatus['status'] ?? '');
                    $dedupeKey = "status:{$statusId}:{$statusName}";

                    if ($statusId !== '' && ! $this->idempotency->acquire($dedupeKey)) {
                        $duplicatesSkipped++;

                        continue;
                    }

                    $status = MessageStatus::fromWebhook($rawStatus, $metadata);

                    $this->events->dispatch(new MessageStatusUpdated($status));

                    match ($status->status) {
                        'delivered' => $this->events->dispatch(new MessageDelivered($status)),
                        'read' => $this->events->dispatch(new MessageRead($status)),
                        'failed' => $this->events->dispatch(new MessageFailed($status)),
                        default => null,
                    };

                    $statusesDispatched++;
                }
            }
        }

        return [
            'messages_dispatched' => $messagesDispatched,
            'statuses_dispatched' => $statusesDispatched,
            'duplicates_skipped' => $duplicatesSkipped,
        ];
    }

    /**
     * @param  mixed  $contacts
     * @return array<string, array<string, mixed>>
     */
    private function indexContacts(mixed $contacts): array
    {
        if (! is_array($contacts)) {
            return [];
        }

        $indexed = [];
        foreach ($contacts as $contact) {
            if (is_array($contact) && isset($contact['wa_id'])) {
                $indexed[(string) $contact['wa_id']] = $contact;
            }
        }

        return $indexed;
    }
}
