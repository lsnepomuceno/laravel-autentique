<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Whose a folder is: the user's own, shared with the organization, or with a
 * group.
 */
enum FolderType: string
{
    case Default = 'DEFAULT';
    case Organization = 'ORGANIZATION';
    case Group = 'GROUP';
}
