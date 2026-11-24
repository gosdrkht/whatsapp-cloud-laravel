<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Support;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Tracks Meta's 24-hour customer service window per WhatsApp phone number.
 *
 * When a user sends an incoming message, a 24-hour window opens during which
 * free-form (non-template) messages can be sent without incurring template charges.
 */
class ConversationWindow
{
    public const WINDOW_SECONDS = 86400;

    public function __construct(
        private readonly CacheFactory $cache,
    ) {}

    public function recordInbound(string $phoneNumber, ?int $timestamp = null): void
    {
        if (! config('whatsapp.service_window.enabled', true)) {
            return;
        }

        $normalized = PhoneNumberNormalizer::normalize($phoneNumber);
        $inboundAt = $timestamp ?? time();
        $expiresAt = $inboundAt + self::WINDOW_SECONDS;
        $ttl = max(1, $expiresAt - time());

        $this->repository()->put($this->key($normalized), $expiresAt, $ttl);
    }

    public function isOpen(string $phoneNumber): bool
    {
        return $this->remainingSeconds($phoneNumber) > 0;
    }

    public function expiresAt(string $phoneNumber): ?int
    {
        $normalized = PhoneNumberNormalizer::normalize($phoneNumber);
        $value = $this->repository()->get($this->key($normalized));

        return is_int($value) ? $value : null;
    }

    public function remainingSeconds(string $phoneNumber): int
    {
        $expiresAt = $this->expiresAt($phoneNumber);
        if ($expiresAt === null) {
            return 0;
        }

        return max(0, $expiresAt - time());
    }

    private function key(string $normalizedPhone): string
    {
        $prefix = (string) config('whatsapp.service_window.prefix', 'whatsapp_window:');

        return $prefix . $normalizedPhone;
    }

    private function repository(): CacheRepository
    {
        $store = config('whatsapp.service_window.store');

        return is_string($store) && $store !== ''
            ? $this->cache->store($store)
            : $this->cache->store();
    }
}
