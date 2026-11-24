<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Prevents duplicate processing of Meta webhook deliveries using atomic cache keys.
 */
class WebhookIdempotencyStore
{
    public function __construct(
        private readonly CacheFactory $cache,
    ) {}

    /**
     * Atomically mark an event key as seen. Returns true if this is the first time
     * the key was seen, or false if it has already been processed.
     */
    public function acquire(string $eventKey): bool
    {
        if (! config('whatsapp.webhook.idempotency.enabled', true)) {
            return true;
        }

        $prefix = (string) config('whatsapp.webhook.idempotency.prefix', 'whatsapp_webhook:');
        $ttl = (int) config('whatsapp.webhook.idempotency.ttl', 86400);

        return $this->repository()->add($prefix . $eventKey, true, $ttl);
    }

    private function repository(): CacheRepository
    {
        $store = config('whatsapp.webhook.idempotency.store');

        return is_string($store) && $store !== ''
            ? $this->cache->store($store)
            : $this->cache->store();
    }
}
