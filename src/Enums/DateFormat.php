<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * How dates are written on the document and to its signers.
 */
enum DateFormat: string
{
    case DayMonthYear = 'DD_MM_YYYY';
    case MonthDayYear = 'MM_DD_YYYY';
}
