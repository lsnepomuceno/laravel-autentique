<?php

declare(strict_types=1);

/**
 * Turning an introspection into the SDL the suite validates against.
 *
 * Development only: `webonyx/graphql-php` is a dev dependency, so none of this
 * may live in src/. `autentique:schema` writes the JSON; this prints it.
 */

use GraphQL\Utils\{BuildClientSchema, SchemaPrinter};

/**
 * The SDL of an introspection, with the header saying where it came from.
 *
 * @param  array<string, mixed>  $introspection  `{"__schema": …}`, as `autentique:schema` writes it.
 */
function printIntrospection(array $introspection, string $date): string
{
    $schema = BuildClientSchema::build($introspection);

    $header = <<<TEXT
        # Autentique API v2, standard endpoint.
        #
        # INTROSPECTED on {$date} with `autentique:schema`, then printed with
        # `composer schema:print`. Every operation file in src/Resources/graphql is
        # validated against this file by tests/GraphQL/SchemaTest.php.

        TEXT;

    return $header . "\n" . SchemaPrinter::doPrint($schema) . "\n";
}
