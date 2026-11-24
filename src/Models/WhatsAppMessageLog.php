<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Opt-in Eloquent model for persisting outbound/inbound WhatsApp message logs,
 * delivery/read timestamps, failure reasons, and Meta pricing categories.
 *
 * @property int $id
 * @property string|null $wamid
 * @property string $direction
 * @property string $phone_number
 * @property string|null $phone_number_id
 * @property string $type
 * @property string|null $template_name
 * @property string $status
 * @property string|null $pricing_category
 * @property bool $billable
 * @property array<string, mixed>|null $payload
 * @property string|null $error_code
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 */
class WhatsAppMessageLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'billable' => 'boolean',
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return (string) config('whatsapp.database_logging.table', 'whatsapp_messages');
    }
}
