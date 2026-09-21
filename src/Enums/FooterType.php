<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * How large the signature footer is.
 */
enum FooterType: string
{
    case Standard = 'STANDARD';
    case Mini = 'MINI';
}
