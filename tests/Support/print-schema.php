<?php

declare(strict_types=1);

/**
 * `composer schema:print`: tests/Resources/introspection.json into
 * tests/Resources/schema.graphql.
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/schema.php';

$source = __DIR__ . '/../Resources/introspection.json';
$target = __DIR__ . '/../Resources/schema.graphql';

$contents = file_get_contents($source);
$introspection = is_string($contents) ? json_decode($contents, true) : null;

if (! is_array($introspection)) {
    throw new RuntimeException('Run autentique:schema --output=tests/Resources/introspection.json first.');
}

/** @var array<string, mixed> $introspection */
file_put_contents($target, printIntrospection($introspection, date('Y-m-d')));

echo "Wrote tests/Resources/schema.graphql.\n";
