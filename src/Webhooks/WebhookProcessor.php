<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use LankaDev\WhatsApp\Events\MessageDelivered;
use LankaDev\WhatsApp\Events\MessageFailed;
use LankaDev\WhatsApp\Events\MessageRead;
use LankaDev\WhatsApp\Events\MessageReceived;
use LankaDev\WhatsApp\Events\MessageStatusUpdated;
use LankaDev\WhatsApp\Events\WebhookReceived;
use LankaDev\WhatsApp\Models\WhatsAppMessageLog;
use LankaDev\WhatsApp\Support\ConversationWindow;

/**
 * Parses incoming Meta WhatsApp webhook payloads, enforces idempotency,
 * updates the 24-hour customer service window, and dispatches typed Laravel events.
 */
class WebhookProcessor
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly WebhookIdempotencyStore $idempotency,
        private readonly ?ConversationWindow $conversationWindow = null,
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

                    if ($from !== '') {
                        $this->conversationWindow?->recordInbound(
                            $from,
                            $message->timestamp > 0 ? $message->timestamp : null,
                        );
                    }

                    $this->logInboundMessage($message);

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

                    $this->logStatusUpdate($status);

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

    private function logInboundMessage(IncomingMessage $message): void
    {
        if (! config('whatsapp.database_logging.enabled', false)) {
            return;
        }

        WhatsAppMessageLog::query()->create([
            'wamid' => $message->id !== '' ? $message->id : null,
            'direction' => 'inbound',
            'phone_number' => $message->from,
            'phone_number_id' => $message->phoneNumberId,
            'type' => $message->type,
            'status' => 'received',
            'payload' => $message->raw,
        ]);
    }

    private function logStatusUpdate(MessageStatus $status): void
    {
        if (! config('whatsapp.database_logging.enabled', false) || $status->id === '') {
            return;
        }

        $log = WhatsAppMessageLog::query()->where('wamid', $status->id)->first();
        if ($log === null) {
            return;
        }

        $updates = [
            'status' => $status->status,
            'billable' => $status->isBillable(),
        ];

        if ($status->pricingCategory() !== null) {
            $updates['pricing_category'] = $status->pricingCategory();
        }

        $eventTime = $status->timestamp > 0 ? Carbon::createFromTimestamp($status->timestamp) : now();

        if ($status->status === 'delivered') {
            $updates['delivered_at'] = $eventTime;
        } elseif ($status->status === 'read') {
            $updates['read_at'] = $eventTime;
        } elseif ($status->status === 'failed') {
            $updates['failed_at'] = $eventTime;
            $updates['error_code'] = $status->firstErrorCode() !== null ? (string) $status->firstErrorCode() : null;
            $updates['error_message'] = $status->firstErrorMessage();
        }

        $log->update($updates);
    }

    /**
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
