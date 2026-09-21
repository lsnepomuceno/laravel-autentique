<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What happens to verification credits when a custom plan renews.
 */
enum CreditsRecurrence: string
{
    case Detached = 'DETACHED';
    case Override = 'OVERRIDE';
}
