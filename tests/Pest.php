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
