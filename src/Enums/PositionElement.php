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
}
