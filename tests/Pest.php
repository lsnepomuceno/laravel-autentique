<?php

declare(strict_types=1);

use LSNepomuceno\LaravelAutentique\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Every test in this directory runs against Testbench's application harness,
| with the package's service provider registered. See tests/TestCase.php.
|
| Shared helpers are defined in this file and nowhere else: a helper defined
| inside one test file is invisible to the others under --parallel, which fails
| as `Call to undefined function`.
|
*/

uses(TestCase::class)->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * The root of the package.
 */
function packageRoot(): string
{
    return dirname(__DIR__);
}

/**
 * The directory holding the package's operation files.
 */
function graphqlDirectory(): string
{
    return packageRoot() . '/src/Resources/graphql';
}

/**
 * The `.graphql` files in one directory of the package's operations.
 *
 * `glob()` rather than `File::glob()`, because the facade returns an untyped
 * array and every caller would have to narrow each path again.
 *
 * @return list<string>
 */
function graphqlFiles(string $directory): array
{
    $files = glob(graphqlDirectory() . "/{$directory}/*.graphql");

    return $files === false ? [] : $files;
}

/**
 * A throwaway directory of operation files, for the loader's failure cases.
 *
 * @param  array<string, string>  $files  Path without extension, to contents.
 */
function graphqlFixture(array $files): string
{
    $directory = sys_get_temp_dir() . '/autentique-graphql-' . bin2hex(random_bytes(6));

    foreach ($files as $name => $contents) {
        $path = "{$directory}/{$name}.graphql";

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), recursive: true);
        }

        file_put_contents($path, $contents);
    }

    return $directory;
}
