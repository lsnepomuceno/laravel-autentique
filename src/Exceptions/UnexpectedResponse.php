<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * Autentique answered, without an error, but without a field the operation
 * selects and the package cannot do without.
 *
 * It means the API and the package disagree about the schema, which is what
 * the schema check exists to catch before a release (docs/spec/graphql-operations.md).
 */
final class UnexpectedResponse extends AutentiqueException
{
    public static function missing(string $field): self
    {
        return new self("Autentique's answer has no usable value for {$field}.");
    }
}
