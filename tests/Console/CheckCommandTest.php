<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

/**
 * `autentique:check`, whose exit code a deployment pipeline reads.
 */

it('says the token is accepted, and whose account it is', function () {
    config()->set('autentique.token', 'the-token');
    Http::fake(['*' => Http::response(responseFixture('me'))]);

    $this->artisan('autentique:check')
        ->expectsOutputToContain('accepted')
        ->expectsOutputToContain('Mateus Zanella <mateus@autentique.com.br>')
        ->expectsOutputToContain('Documents left')
        ->expectsOutputToContain('Autentique is reachable and accepts the token.')
        ->assertSuccessful();
});

it('says when documents are sandbox by default', function () {
    config()->set('autentique.token', 'the-token');
    config()->set('autentique.sandbox', true);
    Http::fake(['*' => Http::response(responseFixture('me'))]);

    $this->artisan('autentique:check')->expectsOutputToContain('yes')->assertSuccessful();
});

it('fails when there is no token, without sending anything', function () {
    config()->set('autentique.token', null);
    Http::fake();

    $this->artisan('autentique:check')
        ->expectsOutputToContain('AUTENTIQUE_TOKEN')
        ->assertFailed();

    Http::assertNothingSent();
});

it('fails when Autentique rejects the token', function () {
    config()->set('autentique.token', 'expired');
    Http::fake(['*' => Http::response(['message' => 'unauthorized'], 401)]);

    $this->artisan('autentique:check')
        ->expectsOutputToContain('not usable')
        ->expectsOutputToContain('Autentique rejected the token.')
        ->assertFailed();
});

it('fails when Autentique is rate limiting the token', function () {
    config()->set('autentique.token', 'the-token');
    config()->set('autentique.retry.times', 0);
    Http::fake(['*' => Http::response(['message' => 'Too Many Attempts.'], 429)]);

    $this->artisan('autentique:check')->expectsOutputToContain('too many attempts')->assertFailed();
});
