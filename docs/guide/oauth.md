# OAuth

An API token acts as the account it belongs to. An application used by other
people, who each authorize it to act in their own Autentique account, uses
OAuth 2.0 instead: Authorization Code with PKCE (S256).

## Setting it up

Register the application in Autentique's dashboard, under applications, and
configure what it gives you:

```ini
AUTENTIQUE_OAUTH_CLIENT_ID=…
AUTENTIQUE_OAUTH_CLIENT_SECRET=…
AUTENTIQUE_OAUTH_REDIRECT_URI=https://your-app.example/autentique/callback
```

The redirect URI must match the registered one exactly.

## Asking for authorization

```php
use LSNepomuceno\LaravelAutentique\Enums\OAuthScope;
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

Route::get('/autentique/connect', function () {
    $authorization = Autentique::oauth()->begin([OAuthScope::UserRead, OAuthScope::DocumentsCreate]);

    session([
        'autentique.state' => $authorization->state,
        'autentique.verifier' => $authorization->verifier,
    ]);

    return redirect($authorization->url);
});
```

| Scope | Allows |
|---|---|
| `UserRead` | `account()->me()` |
| `DocumentsRead` | reading and listing documents |
| `DocumentsCreate` | creating documents |
| `DocumentsUpdate` | changing documents |

`begin(consent: true)` asks the user again even when they already authorized
the application.

## The callback

```php
Route::get('/autentique/callback', function (Request $request) {
    $tokens = Autentique::oauth()->callback(
        $request,
        $request->session()->pull('autentique.state'),
        $request->session()->pull('autentique.verifier'),
    );

    $request->user()->autentiqueConnection()->updateOrCreate([], [
        'access_token' => $tokens->accessToken,
        'refresh_token' => $tokens->refreshToken,
        'expires_at' => $tokens->expiresAt,
    ]);
});
```

`callback()` refuses a `state` that is not the one kept, compared in constant
time, turns a denial (`?error=access_denied`) into `OAuthFailed` with
`$error === 'access_denied'`, and exchanges the code with the verifier.

## Acting in their account

```php
$autentique = Autentique::withToken($connection->access_token);

$autentique->account()->me();
$autentique->newDocument('Agreement')->file($file)->signer($signer)->send();
```

`withToken()` returns the whole API bound to that token; the configured one is
untouched.

## Refreshing

An access token lasts 15 days, a refresh token 365. **A refresh replaces both**,
and the old refresh token must not be sent again, so two workers refreshing the
same pair at once leave one of them holding a token Autentique has already
replaced. Refresh under a lock:

```php
use Illuminate\Support\Facades\Cache;

$tokens = Cache::lock("autentique:refresh:{$connection->id}", 10)->block(5, function () use ($connection) {
    $connection->refresh();

    if (! $connection->expires_at->isPast()) {
        return null;          // another worker refreshed it while this one waited
    }

    $tokens = Autentique::oauth()->refresh($connection->refresh_token);

    $connection->update([
        'access_token' => $tokens->accessToken,
        'refresh_token' => $tokens->refreshToken,
        'expires_at' => $tokens->expiresAt,
    ]);

    return $tokens;
});
```

When a refresh fails with `OAuthFailed` and `$error === 'invalid_grant'`, the
only way back is to ask the user to authorize again.

**Storing the tokens is yours**, encrypted, as you would store any credential
([0011](/decisions/0011-oauth-goes-through-the-same-client)). The package never
stores them, and never refreshes on its own: without your storage and your lock,
an automatic refresh is how refresh tokens get burnt.

## Errors

| Exception | Means | What to do |
|---|---|---|
| `Unauthenticated` | the access token is missing, expired or revoked | refresh, then authorize again if that fails |
| `InsufficientScope` | the token lacks the scope, or the operation is not available through OAuth | authorize again, asking for the scope |
| `OAuthFailed` | a denial, a wrong `state`, or the token endpoint refused; `$error` carries the OAuth code | depends on `$error` |
| `MissingOAuthCredentials` | a client id, secret or redirect URI is not configured | configure it |
