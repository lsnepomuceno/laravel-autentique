<?php

declare(strict_types=1);

use GraphQL\Type\Introspection;
use GraphQL\Utils\BuildSchema;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * `autentique:schema`, against a faked API answering with the assembled schema.
 */

beforeEach(function () {
    config()->set('autentique.token', 'the-token');
});

/**
 * Autentique answering the introspection with the assembled schema.
 */
function fakeIntrospection(): void
{
    $introspection = Introspection::fromSchema(
        BuildSchema::build((string) file_get_contents(packageRoot() . '/tests/Resources/schema.graphql')),
    );

    Http::fake(['*' => Http::response(['data' => $introspection])]);
}

it('writes the introspection to a file', function () {
    fakeIntrospection();
    $output = sys_get_temp_dir() . '/autentique-introspection-' . bin2hex(random_bytes(4)) . '.json';

    $this->artisan('autentique:schema', ['--output' => $output])
        ->expectsOutputToContain('The schema was written to')
        ->assertSuccessful();

    $written = json_decode((string) file_get_contents($output), true);

    expect($written)->toBeArray()->toHaveKey('__schema.types');

    Http::assertSent(fn(Request $request): bool => $request['operationName'] === 'introspection');

    unlink($output);
});

it('prints the introspection when given no file', function () {
    fakeIntrospection();
    $this->artisan('autentique:schema')
        ->expectsOutputToContain('"__schema"')
        ->assertSuccessful();
});

it('fails, saying why, when Autentique refuses the token', function () {
    Http::fake(['*' => Http::response(['message' => 'unauthorized'], 401)]);

    $this->artisan('autentique:schema')
        ->expectsOutputToContain('Autentique rejected the token.')
        ->assertFailed();
});
