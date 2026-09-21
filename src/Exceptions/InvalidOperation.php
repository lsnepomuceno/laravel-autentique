<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * An operation file that cannot be sent as it is: missing, unreadable, or
 * spreading a fragment no file defines.
 *
 * It is a defect in the package rather than in the caller's use of it, and the
 * suite exists to make sure no release carries one.
 */
final class InvalidOperation extends AutentiqueException
{
    public static function missing(string $path): self
    {
        return new self("The GraphQL file {$path} does not exist or cannot be read.");
    }

    public static function unknownFragment(string $fragment, string $operation): self
    {
        return new self("The operation {$operation} spreads the fragment {$fragment}, which no file defines.");
    }
}
