<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Whether a signer stays signed in after signing. Corporate plan.
 */
enum SessionBehavior: string
{
    case Ephemeral = 'EPHEMERAL_SESSION';
    case Keep = 'KEEP_SESSION';
}
