<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The only appearance signers may choose for their signature.
 *
 * `ELETRONIC` is the API's own spelling.
 */
enum SignatureAppearance: string
{
    case Draw = 'DRAW';
    case Handwriting = 'HANDWRITING';
    case Electronic = 'ELETRONIC';
    case Image = 'IMAGE';
}
