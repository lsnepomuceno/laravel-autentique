<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The unit a custom plan renews in.
 */
enum IntervalType: string
{
    case Day = 'DAY';
    case Week = 'WEEK';
    case Month = 'MONTH';
    case Year = 'YEAR';
}
