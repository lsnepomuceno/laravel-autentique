<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Contracts;

use LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException;

/**
 * What this package exposes, resolved from the container or reached through
 * the `Autentique` facade.
 */
interface Autentique
{
    /**
     * Sends a GraphQL document the package does not ship, and returns its
     * `data`.
     *
     * The escape hatch, for a field or an operation this package does not
     * model. Values still travel as variables; the document is the caller's.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    public function query(string $graphql, array $variables = []): array;
}
