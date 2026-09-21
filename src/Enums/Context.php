<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Whose documents or folders an operation looks at: the user's own, their
 * group's, or the whole organization's.
 */
enum Context: string
{
    case User = 'USER';
    case Group = 'GROUP';
    case Organization = 'ORGANIZATION';
}
