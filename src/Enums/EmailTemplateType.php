<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Which email a template is for.
 */
enum EmailTemplateType: string
{
    /** The request to sign. */
    case Solicitation = 'SOLICITATION';

    /** The notice that everyone has signed. */
    case Completed = 'COMPLETED';

    /** The notice a signer receives after signing. */
    case SignatureCompleted = 'SIGNATURE_COMPLETED';
}
