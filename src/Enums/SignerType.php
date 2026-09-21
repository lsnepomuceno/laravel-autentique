<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Whether a signer signs electronically or with a qualified certificate. Per
 * signer on the Corporate plan; for the whole document, see `qualified`.
 */
enum SignerType: string
{
    case Standard = 'STANDARD';
    case Qualified = 'QUALIFIED';
}
