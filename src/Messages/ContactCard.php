<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Messages;

/**
 * Fluent builder for WhatsApp Cloud API contact card payloads.
 */
final class ContactCard
{
    private array $name;

    /** @var array<int, array<string, mixed>> */
    private array $phones = [];

    /** @var array<int, array<string, mixed>> */
    private array $emails = [];

    /** @var array<int, array<string, mixed>> */
    private array $urls = [];

    /** @var array<string, string>|null */
    private ?array $org = null;

    private ?string $birthday = null;

    private function __construct(string $formattedName, ?string $firstName = null, ?string $lastName = null)
    {
        $this->name = ['formatted_name' => $formattedName];

        if ($firstName !== null && $firstName !== '') {
            $this->name['first_name'] = $firstName;
        }

        if ($lastName !== null && $lastName !== '') {
            $this->name['last_name'] = $lastName;
        }
    }

    public static function make(string $formattedName, ?string $firstName = null, ?string $lastName = null): self
    {
        return new self($formattedName, $firstName, $lastName);
    }

    public function addPhone(string $phone, string $type = 'CELL', ?string $waId = null): self
    {
        $entry = [
            'phone' => $phone,
            'type' => strtoupper($type),
        ];

        if ($waId !== null && $waId !== '') {
            $entry['wa_id'] = $waId;
        }

        $this->phones[] = $entry;

        return $this;
    }

    public function addEmail(string $email, string $type = 'WORK'): self
    {
        $this->emails[] = [
            'email' => $email,
            'type' => strtoupper($type),
        ];

        return $this;
    }

    public function addUrl(string $url, string $type = 'WORK'): self
    {
        $this->urls[] = [
            'url' => $url,
            'type' => strtoupper($type),
        ];

        return $this;
    }

    public function organization(string $company, ?string $department = null, ?string $title = null): self
    {
        $this->org = array_filter([
            'company' => $company,
            'department' => $department,
            'title' => $title,
        ], static fn ($value) => $value !== null && $value !== '');

        return $this;
    }

    public function birthday(string $yyyyMmDd): self
    {
        $this->birthday = $yyyyMmDd;

        return $this;
    }

    public function toArray(): array
    {
        $contact = ['name' => $this->name];

        if ($this->phones !== []) {
            $contact['phones'] = $this->phones;
        }

        if ($this->emails !== []) {
            $contact['emails'] = $this->emails;
        }

        if ($this->urls !== []) {
            $contact['urls'] = $this->urls;
        }

        if ($this->org !== null) {
            $contact['org'] = $this->org;
        }

        if ($this->birthday !== null) {
            $contact['birthday'] = $this->birthday;
        }

        return $contact;
    }
}
