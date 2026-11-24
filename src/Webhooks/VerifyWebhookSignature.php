<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Webhooks;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware verifying Meta's X-Hub-Signature-256 header against the raw request body.
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('whatsapp.webhook.verify_signature', true)) {
            return $next($request);
        }

        $appSecret = (string) config('whatsapp.webhook.app_secret', '');
        $signature = $request->header('X-Hub-Signature-256');
        $rawBody = $request->getContent();

        if (! SignatureVerifier::verify($rawBody, is_string($signature) ? $signature : null, $appSecret)) {
            return response()->json([
                'error' => 'Invalid WhatsApp webhook signature.',
            ], 403);
        }

        return $next($request);
    }
}
