<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The kind of document created. A WhatsApp Flow document is a Markdown file
 * signed inside WhatsApp.
 */
enum DocumentType: string
{
    case Default = 'DEFAULT';
    case WhatsappFlow = 'WHATSAPP_FLOW';
}
