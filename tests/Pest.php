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

/**
 * A response fixture from tests/Resources/responses, decoded.
 *
 * Each is copied from Autentique's documentation or recorded from the sandbox
 * with personal data replaced, never written from memory.
 *
 * @return array<string, mixed>
 */
function responseFixture(string $name): array
{
    $decoded = json_decode((string) file_get_contents(__DIR__ . "/Resources/responses/{$name}.json"), true);

    if (! is_array($decoded)) {
        throw new RuntimeException("tests/Resources/responses/{$name}.json is not a JSON object.");
    }

    /** @var array<string, mixed> $decoded */
    return $decoded;
}

/**
 * A file source held in memory, for the upload tests.
 */
function memoryFile(string $name = 'contract.pdf', string $contents = '%PDF-1.7 test'): LSNepomuceno\LaravelAutentique\Contracts\FileSource
{
    return new readonly class ($name, $contents) implements LSNepomuceno\LaravelAutentique\Contracts\FileSource {
        public function __construct(private string $name, private string $contents) {}

        public function name(): string
        {
            return $this->name;
        }

        public function contents(): string
        {
            return $this->contents;
        }
    };
}

/**
 * The exception a callback throws, typed, so its properties can be asserted.
 *
 * Pest's `toThrow()` takes a closure too, but types its parameter as
 * `Throwable`, so PHPStan cannot see the subclass's properties inside it.
 *
 * @template T of Throwable
 *
 * @param  class-string<T>  $class
 * @return T
 */
function thrown(string $class, Closure $callback): Throwable
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($exception instanceof $class) {
            return $exception;
        }

        throw $exception;
    }

    throw new RuntimeException("Expected {$class} to be thrown, and nothing was.");
}

/**
 * The last request the faked HTTP client recorded.
 */
function lastRequest(): Illuminate\Http\Client\Request
{
    $pair = Illuminate\Support\Facades\Http::recorded()->last();

    if (! is_array($pair)) {
        throw new RuntimeException('No request was recorded.');
    }

    return $pair[0];
}

/**
 * The parts of a multipart request, by name, in the order they were sent.
 *
 * @return array<string, array{contents: string, filename: ?string}>
 */
function multipartParts(Illuminate\Http\Client\Request $request): array
{
    $parts = [];

    foreach ($request->data() as $part) {
        if (! is_array($part) || ! is_string($part['name'] ?? null)) {
            continue;
        }

        $parts[$part['name']] = [
            'contents' => is_string($part['contents'] ?? null) ? $part['contents'] : '',
            'filename' => is_string($part['filename'] ?? null) ? $part['filename'] : null,
        ];
    }

    return $parts;
}
