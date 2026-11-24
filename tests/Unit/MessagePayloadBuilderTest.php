<?php

declare(strict_types=1);

use LankaDev\WhatsApp\Exceptions\ValidationException;
use LankaDev\WhatsApp\Messages\ContactCard;
use LankaDev\WhatsApp\Messages\MessagePayloadBuilder;
use LankaDev\WhatsApp\Templates\TemplateComponent;

it('builds text, media, location, contact, reaction, interactive, and template payloads', function (): void {
    $text = MessagePayloadBuilder::text('+94 77 123 4567', 'Hello from Sri Lanka!', true, 'wamid.prev');
    expect($text['to'])->toBe('94771234567')
        ->and($text['text'])->toBe(['preview_url' => true, 'body' => 'Hello from Sri Lanka!'])
        ->and($text['context'])->toBe(['message_id' => 'wamid.prev']);

    $docUrl = MessagePayloadBuilder::document('94771234567', 'https://example.com/invoice.pdf', 'Invoice-1001.pdf');
    expect($docUrl['document'])->toBe([
        'link' => 'https://example.com/invoice.pdf',
        'filename' => 'Invoice-1001.pdf',
    ]);

    $imgId = MessagePayloadBuilder::image('94771234567', '1092837465', 'Receipt');
    expect($imgId['image'])->toBe(['id' => '1092837465', 'caption' => 'Receipt']);

    $location = MessagePayloadBuilder::location('94771234567', 6.9271, 79.8612, 'Colombo', 'Sri Lanka');
    expect($location['location']['name'])->toBe('Colombo');

    $contact = ContactCard::make('Kamal Perera', 'Kamal', 'Perera')
        ->addPhone('+94771234567', 'CELL', '94771234567')
        ->organization('Leading Edge Solutions');
    $contactsPayload = MessagePayloadBuilder::contacts('94771234567', [$contact]);
    expect($contactsPayload['contacts'][0]['name']['formatted_name'])->toBe('Kamal Perera')
        ->and($contactsPayload['contacts'][0]['org']['company'])->toBe('Leading Edge Solutions');

    $buttons = MessagePayloadBuilder::interactiveButtons(
        '94771234567',
        'Confirm your appointment?',
        ['confirm' => 'Yes, Confirm', 'reschedule' => 'Reschedule'],
        'Hospital Reminder',
        'Reply within 24h',
    );
    expect($buttons['interactive']['type'])->toBe('button')
        ->and($buttons['interactive']['action']['buttons'])->toHaveCount(2);

    $template = MessagePayloadBuilder::template(
        '94771234567',
        'order_confirmation',
        'en',
        ['customer' => 'Kamal', 'order_id' => 'ORD-1001'],
        [TemplateComponent::headerDocument('https://example.com/invoice.pdf', 'Invoice-1001.pdf')],
    );
    expect($template['template']['name'])->toBe('order_confirmation')
        ->and($template['template']['components'])->toHaveCount(2)
        ->and($template['template']['components'][1]['parameters'][0])->toBe([
            'type' => 'text',
            'text' => 'Kamal',
            'parameter_name' => 'customer',
        ]);
});

it('rejects invalid interactive button counts and invalid template parameters', function (): void {
    expect(fn () => MessagePayloadBuilder::interactiveButtons('94771234567', 'Pick one', [
        'a' => 'One', 'b' => 'Two', 'c' => 'Three', 'd' => 'Four',
    ]))->toThrow(ValidationException::class);

    expect(fn () => MessagePayloadBuilder::template('94771234567', 'Invalid Name!', 'en'))
        ->toThrow(ValidationException::class);

    expect(fn () => MessagePayloadBuilder::template('94771234567', 'order_confirmation', 'en', [
        'customer' => "Line1\nLine2",
    ]))->toThrow(ValidationException::class);
});
