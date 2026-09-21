<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The plan a child organization is on, as Autentique reports it.
 */
enum ChildOrganizationPlan: string
{
    case Free = 'FREE';
    case Unlimited = 'UNLIMITED';
}
