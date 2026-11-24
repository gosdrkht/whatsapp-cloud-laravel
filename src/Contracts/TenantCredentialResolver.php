<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Contracts;

use LankaDev\WhatsApp\Client\WhatsAppConfig;

/**
 * Contract for SaaS applications to resolve per-tenant WhatsApp Cloud API credentials.
 */
interface TenantCredentialResolver
{
    public function resolve(mixed $tenant): WhatsAppConfig;
}
