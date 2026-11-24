<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Templates;

enum TemplateStatus: string
{
    case APPROVED = 'APPROVED';
    case PENDING = 'PENDING';
    case REJECTED = 'REJECTED';
    case PAUSED = 'PAUSED';
    case DISABLED = 'DISABLED';
    case IN_APPEAL = 'IN_APPEAL';
    case PENDING_DELETION = 'PENDING_DELETION';
    case DELETED = 'DELETED';
    case LIMIT_EXCEEDED = 'LIMIT_EXCEEDED';
}
