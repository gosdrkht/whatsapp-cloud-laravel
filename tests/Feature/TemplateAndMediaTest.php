<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LankaDev\WhatsApp\Facades\WhatsApp;
use LankaDev\WhatsApp\Templates\TemplateCategory;
use LankaDev\WhatsApp\Templates\TemplateStatus;

it('lists templates, checks template status, creates and deletes templates', function (): void {
    Http::fake([
        'https://graph.facebook.com/v21.0/987654321098765/message_templates*' => Http::sequence()
            ->push([
                'data' => [
                    [
                        'id' => 'tpl_1',
                        'name' => 'order_confirmation',
                        'language' => 'en',
                        'category' => 'UTILITY',
                        'status' => 'APPROVED',
                    ],
                ],
            ], 200)
            ->push(['id' => 'tpl_new', 'status' => 'PENDING'], 200)
            ->push(['success' => true], 200),
    ]);

    $status = WhatsApp::templates()->getStatus('order_confirmation', 'en');
    expect($status)->toBe(TemplateStatus::APPROVED);

    $created = WhatsApp::templates()->create([
        'name' => 'payment_receipt',
        'language' => 'en',
        'category' => TemplateCategory::UTILITY,
        'components' => [
            ['type' => 'BODY', 'text' => 'Thank you {{1}} for your payment of {{2}}.'],
        ],
    ]);
    expect($created->json('id'))->toBe('tpl_new');

    $deleted = WhatsApp::templates()->delete('payment_receipt');
    expect($deleted->json('success'))->toBeTrue();
});

it('uploads, inspects, downloads, and deletes media files', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'wa_pdf_');
    file_put_contents($tempFile, "%PDF-1.4\n%Mock PDF content");

    Http::fake([
        'https://graph.facebook.com/v21.0/123456789012345/media' => Http::response([
            'id' => 'media_998877',
        ], 200),
        'https://graph.facebook.com/v21.0/media_998877' => Http::sequence()
            ->push([
                'id' => 'media_998877',
                'url' => 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=media_998877',
                'mime_type' => 'application/pdf',
                'sha256' => 'abc123',
                'file_size' => 25,
            ], 200)
            ->push(['success' => true], 200),
        'https://lookaside.fbsbx.com/whatsapp_business/attachments/*' => Http::response(
            "%PDF-1.4\n%Mock PDF content",
            200,
            ['Content-Type' => 'application/pdf'],
        ),
    ]);

    try {
        $uploadResponse = WhatsApp::media()->upload($tempFile, 'application/pdf');
        expect($uploadResponse->json('id'))->toBe('media_998877');

        $bytes = WhatsApp::media()->download('media_998877');
        expect($bytes)->toContain('%PDF-1.4');

        $deleteResponse = WhatsApp::media()->delete('media_998877');
        expect($deleteResponse->json('success'))->toBeTrue();
    } finally {
        @unlink($tempFile);
    }
});
