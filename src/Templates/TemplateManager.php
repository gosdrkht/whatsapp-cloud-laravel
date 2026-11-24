<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Templates;

use LankaDev\WhatsApp\Client\ApiResponse;
use LankaDev\WhatsApp\Client\WhatsAppClient;

/**
 * Manage WhatsApp Business Account message templates via Meta Cloud API.
 */
class TemplateManager
{
    public function __construct(
        private readonly WhatsAppClient $client,
    ) {}

    /**
     * Retrieve message templates for the configured WhatsApp Business Account.
     *
     * @param  array<string, mixed>  $filters  Optional Meta query filters (e.g., name, status, language, category, limit, after)
     */
    public function list(array $filters = [], ?string $businessAccountId = null): ApiResponse
    {
        return $this->client->get(
            $this->client->config()->templatesEndpoint($businessAccountId),
            $filters,
        );
    }

    /**
     * Find a specific template by name (and optional language code).
     *
     * @return array<string, mixed>|null
     */
    public function find(string $name, ?string $language = null, ?string $businessAccountId = null): ?array
    {
        $filters = ['name' => $name];
        if ($language !== null) {
            $filters['language'] = $language;
        }

        $response = $this->list($filters, $businessAccountId);
        $templates = $response->json('data', []);

        if (! is_array($templates)) {
            return null;
        }

        foreach ($templates as $template) {
            if (($template['name'] ?? null) === $name && ($language === null || ($template['language'] ?? null) === $language)) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Get the current approval status of a template.
     */
    public function getStatus(string $name, ?string $language = null, ?string $businessAccountId = null): ?TemplateStatus
    {
        $template = $this->find($name, $language, $businessAccountId);

        if ($template === null || ! isset($template['status'])) {
            return null;
        }

        return TemplateStatus::tryFrom((string) $template['status']);
    }

    /**
     * Create a new message template under the configured WABA.
     *
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?string $businessAccountId = null): ApiResponse
    {
        if (isset($payload['name'])) {
            TemplateValidator::validateName((string) $payload['name']);
        }

        if (isset($payload['language'])) {
            TemplateValidator::validateLanguage((string) $payload['language']);
        }

        if (isset($payload['category']) && $payload['category'] instanceof TemplateCategory) {
            $payload['category'] = $payload['category']->value;
        }

        return $this->client->post(
            $this->client->config()->templatesEndpoint($businessAccountId),
            $payload,
        );
    }

    /**
     * Delete a template by name (and optional hsm_id).
     */
    public function delete(string $name, ?string $hsmId = null, ?string $businessAccountId = null): ApiResponse
    {
        $query = ['name' => $name];
        if ($hsmId !== null) {
            $query['hsm_id'] = $hsmId;
        }

        return $this->client->delete(
            $this->client->config()->templatesEndpoint($businessAccountId),
            $query,
        );
    }
}
