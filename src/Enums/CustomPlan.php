<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The plan a Corporate account gives a child organization.
 */
enum CustomPlan: string
{
    case Free = 'FREE';
    case Professional = 'PROFESSIONAL';
}
