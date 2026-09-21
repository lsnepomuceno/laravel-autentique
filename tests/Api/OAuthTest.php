<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Api\OAuth;
use LSNepomuceno\LaravelAutentique\Data\OAuthTokens;
use LSNepomuceno\LaravelAutentique\Enums\OAuthScope;
use LSNepomuceno\LaravelAutentique\Exceptions\{InsufficientScope, InvalidInput, MissingOAuthCredentials, OAuthFailed, TransportFailed};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(function () {
    config()->set('autentique.oauth.client_id', 'the-client');
    config()->set('autentique.oauth.client_secret', 'the-client-secret');
    config()->set('autentique.oauth.redirect_uri', 'https://app.example/autentique/callback');
});

/**
 * The query string of a URL, decoded.
 *
 * @return array<string, string>
 */
function queryOf(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    /** @var array<string, string> $query */
    return $query;
}

it('starts an authorization with a state and a PKCE challenge', function () {
    $authorization = Autentique::oauth()->begin([OAuthScope::UserRead, OAuthScope::DocumentsCreate]);

    $query = queryOf($authorization->url);

    expect($authorization->url)->toStartWith('https://api.autentique.com.br/oauth/authorize?')
        ->and($query)->toBe([
            'client_id' => 'the-client',
            'redirect_uri' => 'https://app.example/autentique/callback',
            'response_type' => 'code',
            'scope' => 'user:read documents:create',
            'state' => $authorization->state,
            'code_challenge' => OAuth::challenge($authorization->verifier),
            'code_challenge_method' => 'S256',
        ])
        ->and(strlen($authorization->state))->toBe(40)
        ->and(strlen($authorization->verifier))->toBeGreaterThanOrEqual(43)->toBeLessThanOrEqual(128);
});

it('asks for consent again when told', function () {
    expect(queryOf(Autentique::oauth()->begin([OAuthScope::UserRead], consent: true)->url)['prompt'])->toBe('consent');
});

it('computes the S256 challenge of RFC 7636', function () {
    // The example of RFC 7636, appendix B.
    expect(OAuth::challenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk'))->toBe('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM');
});

it('refuses an authorization without scopes, or without credentials', function () {
    expect(fn() => Autentique::oauth()->begin([]))->toThrow(InvalidInput::class);

    config()->set('autentique.oauth.client_id', null);

    expect(fn() => Autentique::oauth()->begin([OAuthScope::UserRead]))->toThrow(MissingOAuthCredentials::class, 'AUTENTIQUE_OAUTH_CLIENT_ID');
});

it('exchanges the code from the callback for tokens', function () {
    Http::fake(['api.autentique.com.br/oauth/token' => Http::response([
        'access_token' => 'the-access-token',
        'refresh_token' => 'the-refresh-token',
        'token_type' => 'Bearer',
        'expires_in' => 1296000,
    ])]);

    $tokens = Autentique::oauth()->callback(
        Request::create('/autentique/callback', 'GET', ['code' => 'the-code', 'state' => 'the-state']),
        'the-state',
        'the-verifier',
    );

    expect($tokens)->toBeInstanceOf(OAuthTokens::class)
        ->and($tokens->accessToken)->toBe('the-access-token')
        ->and($tokens->refreshToken)->toBe('the-refresh-token')
        ->and($tokens->expiresAt?->isFuture())->toBeTrue()
        ->and($tokens->hasExpired())->toBeFalse();

    Http::assertSent(fn(HttpRequest $request): bool => $request->url() === 'https://api.autentique.com.br/oauth/token'
        && $request->isForm()
        && ! $request->hasHeader('Authorization')
        && $request->data() === [
            'grant_type' => 'authorization_code',
            'client_id' => 'the-client',
            'client_secret' => 'the-client-secret',
            'redirect_uri' => 'https://app.example/autentique/callback',
            'code' => 'the-code',
            'code_verifier' => 'the-verifier',
        ]);
});

it('refuses a callback whose state is not the one sent', function (?string $expected, array $query) {
    Http::fake();

    expect(fn() => Autentique::oauth()->callback(Request::create('/cb', 'GET', $query), $expected, 'v'))
        ->toThrow(OAuthFailed::class, 'state');

    Http::assertNothingSent();
})->with([
    'another state' => ['the-state', ['code' => 'c', 'state' => 'forged']],
    'no state back' => ['the-state', ['code' => 'c']],
    'no state kept' => [null, ['code' => 'c', 'state' => 'the-state']],
]);

it('turns a denial into an exception carrying the OAuth error', function () {
    $exception = thrown(OAuthFailed::class, fn() => Autentique::oauth()->callback(
        Request::create('/cb', 'GET', ['error' => 'access_denied', 'error_description' => 'The user said no', 'state' => 's']),
        's',
        'v',
    ));

    expect($exception->error)->toBe('access_denied')
        ->and($exception->getMessage())->toContain('The user said no');
});

it('refuses a callback without a code', function () {
    Autentique::oauth()->callback(Request::create('/cb', 'GET', ['state' => 's']), 's', 'v');
})->throws(OAuthFailed::class, 'no code');

it('refreshes, replacing both tokens', function () {
    Http::fake(['*' => Http::response(['access_token' => 'new-access', 'refresh_token' => 'new-refresh'])]);

    $tokens = Autentique::oauth()->refresh('old-refresh');

    expect($tokens->accessToken)->toBe('new-access')
        ->and($tokens->refreshToken)->toBe('new-refresh')
        ->and($tokens->expiresAt)->toBeNull();

    Http::assertSent(fn(HttpRequest $request): bool => $request->data() === [
        'grant_type' => 'refresh_token',
        'client_id' => 'the-client',
        'client_secret' => 'the-client-secret',
        'refresh_token' => 'old-refresh',
    ]);
});

it('says why the token endpoint refused', function () {
    Http::fake(['*' => Http::response(['error' => 'invalid_grant', 'error_description' => 'The refresh token is invalid.'], 400)]);

    $exception = thrown(OAuthFailed::class, fn() => Autentique::oauth()->refresh('burnt'));

    expect($exception->error)->toBe('invalid_grant')
        ->and($exception->status)->toBe(400)
        ->and($exception->getMessage())->toContain('The refresh token is invalid.')
        ->and($exception->getMessage())->not->toContain('burnt');
});

it('says when the token endpoint cannot be reached', function () {
    Http::fake(fn() => throw new Illuminate\Http\Client\ConnectionException('timed out'));

    Autentique::oauth()->refresh('r');
})->throws(TransportFailed::class);

it('sends the whole API with another token, leaving the configured one alone', function () {
    config()->set('autentique.token', 'the-token');
    Http::fake(['*' => Http::response(responseFixture('me'))]);

    Autentique::withToken('an-access-token')->account()->me();
    Autentique::account()->me();

    $tokens = Http::recorded()->map(fn(array $pair): array => $pair[0]->header('Authorization'))->all();

    expect($tokens)->toBe([['Bearer an-access-token'], ['Bearer the-token']]);
});

it('says when the token lacks a scope', function () {
    config()->set('autentique.token', 'the-token');
    Http::fake(['*' => Http::response(responseFixture('errors/unauthorized-scope'))]);

    Autentique::withToken('narrow')->documents()->list();
})->throws(InsufficientScope::class);

it('exchanges fake tokens in the fake, recording the grant', function () {
    $fake = Autentique::fake();

    $tokens = Autentique::oauth()->refresh('r');

    expect($tokens->accessToken)->toStartWith('fake-access-token')
        ->and($fake->sent()[0]->variables)->toBe(['grant_type' => 'refresh_token']);

    Autentique::withToken($tokens->accessToken)->account()->me();

    expect($fake->sent(Operation::Me)[0]->token)->toBe($tokens->accessToken);
});
