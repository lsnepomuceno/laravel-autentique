<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * How often signers who have not acted are reminded by email: daily up to
 * seven times, weekly up to four.
 */
enum Reminder: string
{
    case Daily = 'DAILY';
    case Weekly = 'WEEKLY';
}
