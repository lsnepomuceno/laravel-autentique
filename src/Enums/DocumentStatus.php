<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Where a document stands, for filtering a listing.
 */
enum DocumentStatus: string
{
    /** Waiting for at least one signer. */
    case Pending = 'PENDING';

    /** Signed by everyone. */
    case Signed = 'SIGNED';

    /** Refused by a signer. */
    case NotSigned = 'NOT_SIGNED';

    /** In the trash. */
    case Deleted = 'DELETED';
}
