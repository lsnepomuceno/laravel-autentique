<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The wording of the message a WhatsApp signer receives.
 */
enum WhatsappTemplate: string
{
    case Standard = 'STANDARD';
    case Formal = 'FORMAL';
    case Casual = 'CASUAL';
    case Direct = 'DIRECT';
}
