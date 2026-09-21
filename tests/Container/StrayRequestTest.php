<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

/**
 * The suite never reaches Autentique (docs/spec/invariants.md, rule 5).
 *
 * tests/TestCase.php refuses every request nobody faked. This asserts that it
 * does, because a harness that quietly stopped refusing would leave every
 * other test green while the code under it went out to the network.
 */
it('refuses a request nobody faked', function () {
    Http::get('https://api.autentique.com.br/v2/graphql');
})->throws(RuntimeException::class, 'Attempted request to [https://api.autentique.com.br/v2/graphql] without a matching fake.');

it('lets a faked request through', function () {
    Http::fake(['api.autentique.com.br/*' => Http::response(['data' => []])]);

    expect(Http::post('https://api.autentique.com.br/v2/graphql')->json())->toBe(['data' => []]);
});
