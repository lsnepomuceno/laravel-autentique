<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

/**
 * What a consumer actually receives from Packagist.
 *
 * Everything built for testing is **development tooling and must not reach
 * production**: not the fixtures, not the Docker files, not the configuration
 * of tools a consumer never runs. `.gitattributes` says so with
 * `export-ignore`, and nothing checks that it still does unless this does.
 *
 * In laravel-a1-pdf-sign it had already drifted when the check was written:
 * `phpstan.neon`, `pint.json`, `composer-dependency-analyser.php` and
 * `package-lock.json` were all being shipped, because each was added later
 * than the list.
 */

/**
 * @return list<string>
 */
function distributedFiles(): array
{
    // git archive is what Packagist builds a dist from, so asking it is asking
    // the question consumers experience rather than a proxy for it.
    //
    // It reads the committed .gitattributes, not the working tree, so an
    // uncommitted change to that file shows up one commit late here and is
    // always current in CI, which checks out a commit.
    $listing = Process::path(packageRoot())
        ->run('git archive HEAD | tar t')
        ->throw()
        ->output();

    $paths = [];

    foreach (explode("\n", $listing) as $line) {
        $path = trim($line);

        // Directory entries end in a slash and say nothing a file does not.
        if ($path !== '' && ! str_ends_with($path, '/')) {
            $paths[] = $path;
        }
    }

    return $paths;
}

it('ships the package and nothing built for testing it', function () {
    // Anything outside these is either a development tool or an oversight.
    $allowed = ['src/', 'config/'];
    $files = ['composer.json', 'composer.lock', 'LICENSE.md', 'README.md', 'UPGRADE.md'];

    $unexpected = [];

    foreach (distributedFiles() as $path) {
        $expected = in_array($path, $files, true);

        foreach ($allowed as $prefix) {
            $expected = $expected || str_starts_with($path, $prefix);
        }

        if (! $expected) {
            $unexpected[] = $path;
        }
    }

    expect($unexpected)->toBe([]);
});

it('ships none of the development tooling', function () {
    $shipped = implode("\n", distributedFiles());

    foreach ([
        'tests/',
        'docs/',
        '.docker/',
        '.github/',
        '.husky/',
        'ARCHITECTURE.md',
        'CLAUDE.md',
        'phpstan.neon',
        'pint.json',
        'composer-dependency-analyser.php',
        'package.json',
        'package-lock.json',
        'phpunit.xml',
    ] as $path) {
        expect($shipped)->not->toContain($path);
    }
});

it('still ships the things a consumer needs', function () {
    // The other half of the rule: trimming the archive must not take the
    // package with it.
    expect(distributedFiles())->toContain('composer.json')
        ->toContain('config/autentique.php')
        ->toContain('LICENSE.md')
        ->toContain('README.md')
        ->toContain('src/LaravelAutentiqueServiceProvider.php')
        ->toContain('src/Facades/Autentique.php')
        // The operations are read at runtime, so a release without them is a
        // release that cannot send a single request.
        ->toContain('src/Resources/graphql/mutations/createDocument.graphql')
        ->toContain('src/Resources/graphql/fragments/DocumentFields.graphql');
});
