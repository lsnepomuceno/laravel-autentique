<?php

declare(strict_types=1);

/**
 * Architectural rules, executable.
 *
 * The rules the decision records describe are checked on every run, so the
 * architecture cannot erode silently after a merge. Several rules below reach
 * namespaces that are still empty: they are written before the code they
 * constrain, so that the first class to land there lands under them.
 */
arch('no debug leftovers ship')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'die', 'exit', 'ray'])
    ->not->toBeUsed();

arch('no weak hashing or insecure randomness')
    ->expect(['md5', 'sha1', 'rand', 'srand', 'mt_rand', 'uniqid'])
    ->not->toBeUsed();

arch('no eval or dynamic code execution')
    ->expect(['eval', 'create_function'])
    ->not->toBeUsed();

arch('contracts are interfaces')
    ->expect('LSNepomuceno\LaravelAutentique\Contracts')
    ->toBeInterfaces();

arch('facades only proxy contracts')
    ->expect('LSNepomuceno\LaravelAutentique\Facades')
    ->toExtend('Illuminate\Support\Facades\Facade')
    ->toBeFinal();

arch('value objects are immutable and closed for extension')
    ->expect('LSNepomuceno\LaravelAutentique\Data')
    ->toBeReadonly()
    ->toBeFinal();

/**
 * String backed, so a configuration file can name a case in plain text and
 * `config:cache` can serialise it, and so a value read from the API maps to a
 * case with `from()`.
 */
arch('enums are string backed')
    ->expect('LSNepomuceno\LaravelAutentique\Enums')
    ->toBeStringBackedEnums();

arch('console commands stay in Commands')
    ->expect('Illuminate\Console\Command')
    ->toOnlyBeUsedIn('LSNepomuceno\LaravelAutentique\Commands');

/**
 * One transport, so `Http::fake()` and `preventStrayRequests()` in a consuming
 * application reach every request this package makes, uploads included
 * (docs/decisions/0004-the-transport-is-laravels-http-client.md).
 *
 * The class named here does not exist yet. The rule is written first, so the
 * first request anywhere else fails the suite rather than a review.
 */
arch('only the GraphQL client reaches the network')
    ->expect([
        'Illuminate\Http\Client',
        'Illuminate\Support\Facades\Http',
        'GuzzleHttp',
        'Psr\Http\Client',
        'curl_init',
        'curl_exec',
        'curl_multi_exec',
        'fsockopen',
        'stream_socket_client',
    ])
    ->toOnlyBeUsedIn('LSNepomuceno\LaravelAutentique\GraphQL\Client');

/**
 * Every exception extends the package's base, so a caller catches everything
 * this package throws in one place
 * (docs/decisions/0005-exceptions-name-the-real-fault.md).
 *
 * A walk rather than an arch expectation, so the base itself is exempt by
 * name and the rule still holds for every other class.
 */
it('keeps every exception under the package base', function () {
    $base = 'LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException';
    $wrong = [];

    $files = glob(packageRoot() . '/src/Exceptions/*.php');

    foreach ($files === false ? [] : $files as $file) {
        $name = 'LSNepomuceno\LaravelAutentique\Exceptions\\' . basename($file, '.php');

        if ($name === $base || interface_exists($name) || enum_exists($name)) {
            continue;
        }

        if (! is_subclass_of($name, $base)) {
            $wrong[] = $name;
        }
    }

    expect($wrong)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| GraphQL operations
|--------------------------------------------------------------------------
|
| Operations are written in `.graphql` files and nowhere else, and values reach
| them only as variables (docs/decisions/0003-operations-live-in-graphql-files.md).
| laravel-autentique-v2 pasted values into the query text with str_replace,
| which is GraphQL injection, and a misnamed placeholder sent the literal
| `$folderId` to the API.
|
*/

/**
 * The GraphQL operations written inside a piece of PHP.
 *
 * Only string literals and heredocs are read, so a comment may still describe
 * an operation. What counts is the start of a definition, `query Name (` or
 * `mutation {`, which prose does not produce.
 *
 * @return list<string>
 */
function operationsWrittenIn(string $source): array
{
    $found = [];

    foreach (token_get_all($source) as $token) {
        if (! is_array($token) || ! in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
            continue;
        }

        if (preg_match('/\b(query|mutation|subscription|fragment)\b\s*[A-Za-z_]*\s*[({]/', $token[1], $match) === 1) {
            $found[] = $token[2] . ': ' . $match[0];
        }
    }

    return $found;
}

it('writes no GraphQL operation in PHP', function () {
    $found = [];

    foreach (phpFilesUnder(packageRoot() . '/src') as $path => $contents) {
        foreach (operationsWrittenIn($contents) as $operation) {
            $found[] = "{$path}:{$operation}";
        }
    }

    expect($found)->toBe([]);
});

it('finds an operation written in PHP, and leaves prose alone', function () {
    // The rule has to be able to fail, on the shapes v2 actually wrote.
    $source = <<<'PHP'
        <?php
        // A comment may say mutation createDocument { … } freely.
        $a = 'mutation CreateDocument($document: DocumentInput!) { id }';
        $b = "query { document(id: \"{$id}\") { id } }";
        $c = 'the query that lists documents';
        PHP;

    expect(operationsWrittenIn($source))->toHaveCount(2);
});

/*
|--------------------------------------------------------------------------
| Secrets
|--------------------------------------------------------------------------
*/

/**
 * The token and the webhook secret never reach a stack trace.
 *
 * `#[\SensitiveParameter]` replaces the value with a placeholder in every
 * trace PHP builds, which is what keeps a token out of an error tracker when a
 * request fails. Checked by name, because a parameter holding a secret is
 * always called one of these.
 */
it('marks every secret parameter as sensitive', function () {
    $secrets = ['token', 'secret', 'password', 'accessToken', 'refreshToken', 'clientSecret'];
    $found = [];

    foreach (phpFilesUnder(packageRoot() . '/src') as $path => $contents) {
        $class = 'LSNepomuceno\LaravelAutentique\\' . str_replace(['src/', '.php', '/'], ['', '', '\\'], $path);

        if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
            continue;
        }

        foreach (new ReflectionClass($class)->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {
                if (in_array($parameter->getName(), $secrets, true)
                    && $parameter->getAttributes(SensitiveParameter::class) === []) {
                    $found[] = "{$class}::{$method->getName()}(\${$parameter->getName()})";
                }
            }
        }
    }

    expect($found)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Docblocks
|--------------------------------------------------------------------------
|
| Checked mechanically, because prose rots quietly. Both rules describe defects
| that shipped in laravel-a1-pdf-sign rather than defects worth worrying about:
| a docblock that documents nothing is a comment nobody reads, and one that
| documents the wrong thing is worse than none, because it is believed.
|
*/

/**
 * Every PHP file under a directory, as relative path to contents.
 *
 * @return Generator<string, string>
 */
function phpFilesUnder(string $directory): Generator
{
    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
        if ($file->getExtension() === 'php') {
            yield str_replace(packageRoot() . '/', '', $file->getPathname()) => (string) file_get_contents($file->getPathname());
        }
    }
}

it('never leaves a docblock documenting another docblock', function (string $directory) {
    // What this catches: inserting a method between a docblock and the method
    // it described, which leaves the first one attached to the newcomer and the
    // original method undocumented. Both blocks look fine in isolation, and the
    // diff looks like an addition.
    $found = [];

    foreach (phpFilesUnder(packageRoot() . '/' . $directory) as $path => $contents) {
        $lines = explode("\n", $contents);

        foreach ($lines as $number => $line) {
            if (trim($line) === '*/' && str_starts_with(trim($lines[$number + 1] ?? ''), '/**')) {
                $found[] = "{$path}:" . ($number + 2);
            }
        }
    }

    expect($found)->toBe([]);
})->with(['src', 'tests']);

it('documents parameters that exist', function () {
    // A @param surviving a rename or a removal is the other half of the same
    // problem: the signature moved and the prose did not.
    $found = [];

    foreach (phpFilesUnder(packageRoot() . '/src') as $path => $contents) {
        preg_match_all(
            '#/\*\*(.*?)\*/\s*(?:\#\[[^\]]*\]\s*)*(?:(?:public|private|protected|final|static|abstract)\s+)*function\s+(\w+)\s*\((.*?)\)\s*[:{]#s',
            $contents,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as [, $doc, $method, $parameters]) {
            preg_match_all('/@param\s+\S+\s+\$(\w+)/', $doc, $documented);

            foreach ($documented[1] as $name) {
                if (preg_match('/\$' . $name . '\b/', $parameters) !== 1) {
                    $found[] = "{$path}: {$method}() documents \${$name}";
                }
            }
        }
    }

    expect($found)->toBe([]);
});

/**
 * The front door describes the package.
 *
 * In laravel-a1-pdf-sign two facade methods were public for a release before
 * the README mentioned either. The check is deliberately shallow: it asks
 * whether the name appears, not whether what is written about it is any good,
 * because only the second is worth a human's time and only the first can be
 * checked at all.
 */
it('names every entry point on the front page', function () {
    $readme = (string) file_get_contents(packageRoot() . '/README.md');
    $missing = [];

    foreach (new ReflectionClass(LSNepomuceno\LaravelAutentique\Contracts\Autentique::class)->getMethods() as $method) {
        if (! str_contains($readme, $method->getName())) {
            $missing[] = $method->getName();
        }
    }

    expect($missing)->toBe([]);
});

/*
|--------------------------------------------------------------------------
| Strict types
|--------------------------------------------------------------------------
|
| `pint.json` writes the declaration, so this exists for the case Pint cannot
| reach: a file added outside the formatter's path, or the rule being switched
| off. The arch expectation covers `src/`, and cannot cover `tests/` or
| `config/`, whose files declare no class. Hence the walk as well.
|
*/

arch('src declares strict types')
    ->expect('LSNepomuceno\LaravelAutentique')
    ->toUseStrictTypes();

it('declares strict types in every file, including the ones with no class in them', function () {
    $missing = [];

    foreach (['/src', '/tests', '/config'] as $directory) {
        foreach (phpFilesUnder(packageRoot() . $directory) as $path => $contents) {
            if (! str_contains($contents, 'declare(strict_types=1);')) {
                $missing[] = $path;
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([]);
});
