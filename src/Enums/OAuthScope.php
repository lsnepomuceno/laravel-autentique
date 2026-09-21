<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What an OAuth application may do in the account that authorizes it.
 */
enum OAuthScope: string
{
    /** `me`: the authorizing user's account data. */
    case UserRead = 'user:read';

    /** Read one document, and list documents. */
    case DocumentsRead = 'documents:read';

    case DocumentsCreate = 'documents:create';

    case DocumentsUpdate = 'documents:update';
}
