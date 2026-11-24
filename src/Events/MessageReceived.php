<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LankaDev\WhatsApp\Webhooks\IncomingMessage;

class MessageReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly IncomingMessage $message,
    ) {}
}
