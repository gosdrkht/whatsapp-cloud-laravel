<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Templates;

/**
 * Meta WhatsApp Business template categories (aligned with per-delivered-template-message pricing).
 */
enum TemplateCategory: string
{
    case AUTHENTICATION = 'AUTHENTICATION';
    case MARKETING = 'MARKETING';
    case UTILITY = 'UTILITY';
}
