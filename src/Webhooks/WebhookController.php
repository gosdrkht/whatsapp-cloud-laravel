<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles Meta WhatsApp webhook verification (GET) and event delivery (POST).
 */
class WebhookController
{
    public function __construct(
        private readonly WebhookProcessor $processor,
    ) {}

    /**
     * Verify webhook subscription challenge sent by Meta.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $expectedToken = (string) config('whatsapp.webhook.verify_token', '');

        if (
            $mode === 'subscribe'
            && $expectedToken !== ''
            && is_string($token)
            && hash_equals($expectedToken, $token)
            && is_string($challenge)
        ) {
            return response($challenge, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response('Forbidden', 403);
    }

    /**
     * Receive and process incoming Meta webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $summary = $this->processor->process($payload);

        return response()->json([
            'status' => 'ok',
            'summary' => $summary,
        ]);
    }
}
