<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What is stamped at a position on the page once the signer acts.
 */
enum PositionElement: string
{
    case Signature = 'SIGNATURE';
    case Name = 'NAME';
    case Initials = 'INITIALS';
    case Date = 'DATE';
    case Cpf = 'CPF';

    /** Present in the API's schema; the documentation does not describe it. */
    case Radio = 'RADIO';

    /** Present in the API's schema; the documentation does not describe it. */
    case SquareInitials = 'SQUARE_INITIALS';
}
