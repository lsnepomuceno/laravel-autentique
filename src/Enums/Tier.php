<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The tier of features a custom plan gives.
 */
enum Tier: string
{
    case Free = 'FREE';
    case Professional = 'PROFESSIONAL';
}
