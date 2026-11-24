<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Testing;

use Closure;
use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Client\WhatsAppConfig;
use LankaDev\WhatsApp\WhatsAppManager;
use PHPUnit\Framework\Assert;

/**
 * In-memory fake for WhatsApp::fake() enabling fast, zero-network assertions in Pest & PHPUnit.
 */
class WhatsAppFake extends WhatsAppManager
{
    /** @var array<int, array{payload: array<string, mixed>, phone_number_id: ?string}> */
    private array $sentMessages = [];

    /** @var array<int, string> */
    private array $markedAsRead = [];

    /** @var array<int, ApiResponse> */
    private array $queuedResponses = [];

    public function __construct(?WhatsAppClient $client = null)
    {
        parent::__construct(
            $client ?? new WhatsAppClient(new WhatsAppConfig(
                accessToken: 'fake-token',
                phoneNumberId: '000000000000000',
                businessAccountId: '000000000000001',
            )),
        );
    }

    /**
     * Queue custom mock ApiResponse objects to be returned by subsequent sends.
     */
    public function pushResponse(ApiResponse $response): self
    {
        $this->queuedResponses[] = $response;

        return $this;
    }

    public function sendPayload(array $payload, ?string $phoneNumberId = null): ApiResponse
    {
        $this->sentMessages[] = [
            'payload' => $payload,
            'phone_number_id' => $phoneNumberId,
        ];

        if ($this->queuedResponses !== []) {
            return array_shift($this->queuedResponses);
        }

        $wamid = 'wamid.fake_' . bin2hex(random_bytes(8));

        return ApiResponse::fake([
            'messaging_product' => 'whatsapp',
            'contacts' => [
                [
                    'input' => $payload['to'] ?? '',
                    'wa_id' => $payload['to'] ?? '',
                ],
            ],
            'messages' => [
                ['id' => $wamid],
            ],
        ]);
    }

    public function markAsRead(string $messageId, ?string $phoneNumberId = null): ApiResponse
    {
        $this->markedAsRead[] = $messageId;

        return ApiResponse::fake(['success' => true]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sent(?Closure $callback = null): array
    {
        $payloads = array_map(static fn (array $entry): array => $entry['payload'], $this->sentMessages);

        if ($callback === null) {
            return $payloads;
        }

        return array_values(array_filter($payloads, $callback));
    }

    public function assertSent(Closure|int|null $callback = null): void
    {
        if (is_int($callback)) {
            $this->assertSentCount($callback);

            return;
        }

        $matches = $this->sent($callback);

        Assert::assertNotEmpty(
            $matches,
            'Expected at least one matching WhatsApp message to be sent, but none were recorded.',
        );
    }

    public function assertNothingSent(): void
    {
        Assert::assertCount(
            0,
            $this->sentMessages,
            sprintf('Expected no WhatsApp messages to be sent, but %d were sent.', count($this->sentMessages)),
        );
    }

    public function assertSentCount(int $count): void
    {
        Assert::assertCount(
            $count,
            $this->sentMessages,
            sprintf('Expected %d WhatsApp messages to be sent, got %d.', $count, count($this->sentMessages)),
        );
    }

    public function assertSentTo(string $to): void
    {
        $normalized = preg_replace('/[^0-9]/', '', $to) ?? $to;

        $this->assertSent(static fn (array $payload): bool => ($payload['to'] ?? null) === $normalized);
    }

    public function assertSentText(string $to, ?string $contains = null): void
    {
        $normalized = preg_replace('/[^0-9]/', '', $to) ?? $to;

        $this->assertSent(static function (array $payload) use ($normalized, $contains): bool {
            if (($payload['to'] ?? null) !== $normalized || ($payload['type'] ?? null) !== 'text') {
                return false;
            }

            if ($contains === null) {
                return true;
            }

            return str_contains((string) ($payload['text']['body'] ?? ''), $contains);
        });
    }

    public function assertSentTemplate(string $to, string $templateName, ?string $language = null): void
    {
        $normalized = preg_replace('/[^0-9]/', '', $to) ?? $to;

        $this->assertSent(static function (array $payload) use ($normalized, $templateName, $language): bool {
            if (($payload['to'] ?? null) !== $normalized || ($payload['type'] ?? null) !== 'template') {
                return false;
            }

            if (($payload['template']['name'] ?? null) !== $templateName) {
                return false;
            }

            if ($language !== null && ($payload['template']['language']['code'] ?? null) !== $language) {
                return false;
            }

            return true;
        });
    }

    public function assertSentDocument(string $to, ?string $filename = null): void
    {
        $normalized = preg_replace('/[^0-9]/', '', $to) ?? $to;

        $this->assertSent(static function (array $payload) use ($normalized, $filename): bool {
            if (($payload['to'] ?? null) !== $normalized || ($payload['type'] ?? null) !== 'document') {
                return false;
            }

            if ($filename !== null && ($payload['document']['filename'] ?? null) !== $filename) {
                return false;
            }

            return true;
        });
    }

    public function assertMarkedAsRead(string $messageId): void
    {
        Assert::assertContains(
            $messageId,
            $this->markedAsRead,
            "Expected WhatsApp message [{$messageId}] to be marked as read.",
        );
    }
}
