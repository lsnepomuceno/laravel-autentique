<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return new Configuration()
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)

    /*
     * The suite installs laravel/framework through Testbench, which provides
     * every Illuminate namespace, so the analyser attributes those symbols
     * there rather than to the split packages this library actually requires.
     */
    ->ignoreErrorsOnPackages(
        ['illuminate/console', 'illuminate/contracts', 'illuminate/filesystem', 'illuminate/http', 'illuminate/routing', 'illuminate/support'],
        [ErrorType::UNUSED_DEPENDENCY],
    )
    ->ignoreErrorsOnPackage('laravel/framework', [ErrorType::SHADOW_DEPENDENCY])

    /*
     * Dev-only tooling reached through Pest's global functions and Testbench's
     * base class, neither of which is a direct require.
     */
    ->ignoreErrorsOnPackages(
        ['orchestra/testbench-core', 'pestphp/pest-plugin-arch'],
        [ErrorType::SHADOW_DEPENDENCY],
    )

    /*
     * src/Testing asserts with PHPUnit, as Laravel's own fakes do. It runs only
     * inside a consumer's test suite, where PHPUnit is installed, and requiring
     * it would put a test framework in every production install. The suite
     * reaches PHPUnit's exceptions too, through Pest, which brings it.
     */
    ->ignoreErrorsOnPackageAndPaths(
        'phpunit/phpunit',
        [__DIR__ . '/src/Testing', __DIR__ . '/tests'],
        [ErrorType::SHADOW_DEPENDENCY],
    );
