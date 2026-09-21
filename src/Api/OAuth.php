<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\{Arr, Str};
use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Data\{Authorization, OAuthTokens};
use LSNepomuceno\LaravelAutentique\Enums\OAuthScope;
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput, MissingOAuthCredentials, OAuthFailed};
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * OAuth 2.0, Authorization Code with PKCE, for an application acting on behalf
 * of accounts that authorize it (docs/decisions/0011-oauth-goes-through-the-same-client.md).
 *
 * ```php
 * // Sending the user to Autentique:
 * $authorization = Autentique::oauth()->begin([OAuthScope::UserRead, OAuthScope::DocumentsCreate]);
 * session(['autentique.state' => $authorization->state, 'autentique.verifier' => $authorization->verifier]);
 * return redirect($authorization->url);
 *
 * // When they come back:
 * $tokens = Autentique::oauth()->callback($request, session('autentique.state'), session('autentique.verifier'));
 * ```
 *
 * Storing the tokens, and refreshing them under a lock, is the application's.
 */
final readonly class OAuth
{
    public function __construct(
        private GraphQLClient $client,
        private Repository $config,
    ) {}

    /**
     * Where to send the user, and the `state` and PKCE verifier to keep in
     * their session until they come back.
     *
     * @param  list<OAuthScope>  $scopes
     * @param  bool  $consent  Asks again even when the user already authorized.
     *
     * @throws InvalidInput
     * @throws MissingOAuthCredentials
     */
    public function begin(array $scopes, bool $consent = false): Authorization
    {
        if ($scopes === []) {
            throw new InvalidInput('An authorization asks for at least one scope.');
        }

        $state = Str::random(40);
        $verifier = Str::random(96);

        $query = Arr::query(array_filter([
            'client_id' => $this->credential('client_id'),
            'redirect_uri' => $this->credential('redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', array_map(fn(OAuthScope $scope): string => $scope->value, $scopes)),
            'state' => $state,
            'code_challenge' => self::challenge($verifier),
            'code_challenge_method' => 'S256',
            'prompt' => $consent ? 'consent' : null,
        ], fn(?string $value): bool => $value !== null));

        return new Authorization("{$this->url()}/authorize?{$query}", $state, $verifier);
    }

    /**
     * Handles the request Autentique redirects the user back with: refuses a
     * `state` that is not the one kept, turns a denial into an exception, and
     * exchanges the code.
     *
     * @throws OAuthFailed
     * @throws MissingOAuthCredentials
     * @throws AutentiqueException
     */
    public function callback(Request $request, ?string $expectedState, #[\SensitiveParameter] ?string $verifier): OAuthTokens
    {
        $state = $request->query('state');

        if (! is_string($expectedState) || ! is_string($state) || ! hash_equals($expectedState, $state)) {
            throw new OAuthFailed('The state that came back is not the one this session sent. The callback is refused.');
        }

        $error = $request->query('error');

        if (is_string($error)) {
            $description = $request->query('error_description');

            throw new OAuthFailed(
                "The authorization was not granted: {$error}" . (is_string($description) ? ", {$description}" : '.'),
                $error,
            );
        }

        $code = $request->query('code');

        if (! is_string($code) || $code === '' || ! is_string($verifier) || $verifier === '') {
            throw new OAuthFailed('The callback carries no code, or the session no verifier.');
        }

        return $this->exchange($code, $verifier);
    }

    /**
     * Exchanges an authorization code for tokens.
     *
     * @throws MissingOAuthCredentials
     * @throws AutentiqueException
     */
    public function exchange(string $code, #[\SensitiveParameter] string $verifier): OAuthTokens
    {
        return $this->tokens([
            'grant_type' => 'authorization_code',
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->credential('client_secret'),
            'redirect_uri' => $this->credential('redirect_uri'),
            'code' => $code,
            'code_verifier' => $verifier,
        ]);
    }

    /**
     * A new pair for a refresh token. **Both tokens are replaced**: store the
     * new pair, and never send the old refresh token again.
     *
     * @throws MissingOAuthCredentials
     * @throws AutentiqueException
     */
    public function refresh(#[\SensitiveParameter] string $refreshToken): OAuthTokens
    {
        return $this->tokens([
            'grant_type' => 'refresh_token',
            'client_id' => $this->credential('client_id'),
            'client_secret' => $this->credential('client_secret'),
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * The S256 challenge for a verifier: the unpadded base64url of its SHA-256.
     */
    public static function challenge(#[\SensitiveParameter] string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * @param  array<string, string>  $form
     *
     * @throws AutentiqueException
     */
    private function tokens(#[\SensitiveParameter] array $form): OAuthTokens
    {
        return OAuthTokens::fromPayload(Payload::of($this->client->oauthToken($form)));
    }

    /**
     * @throws MissingOAuthCredentials
     */
    private function credential(string $key): string
    {
        $value = $this->config->get("autentique.oauth.{$key}");

        if (! is_string($value) || $value === '') {
            throw MissingOAuthCredentials::for($key);
        }

        return $value;
    }

    private function url(): string
    {
        $url = $this->config->get('autentique.oauth.url');

        return rtrim(is_string($url) ? $url : 'https://api.autentique.com.br/oauth', '/');
    }
}
