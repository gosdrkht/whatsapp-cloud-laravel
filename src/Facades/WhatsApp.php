<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Facades;

use Illuminate\Support\Facades\Facade;
use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Media\MediaManager;
use LankaDev\WhatsApp\PendingMessage;
use LankaDev\WhatsApp\Templates\TemplateManager;
use LankaDev\WhatsApp\Testing\WhatsAppFake;
use LankaDev\WhatsApp\WhatsAppManager;

/**
 * @method static PendingMessage to(string $recipient)
 * @method static ApiResponse sendPayload(array $payload, ?string $phoneNumberId = null)
 * @method static ApiResponse markAsRead(string $messageId, ?string $phoneNumberId = null)
 * @method static TemplateManager templates()
 * @method static MediaManager media()
 * @method static WhatsAppClient client()
 *
 * @see WhatsAppManager
 */
class WhatsApp extends Facade
{
    /**
     * Replace the bound WhatsAppManager with an in-memory fake for testing.
     */
    public static function fake(): WhatsAppFake
    {
        $fake = new WhatsAppFake;

        static::swap($fake);
        static::getFacadeApplication()?->instance(WhatsAppManager::class, $fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return WhatsAppManager::class;
    }
}
