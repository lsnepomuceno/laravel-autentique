# Getting started

```bash
composer require lsnepomuceno/laravel-autentique
```

Nothing to register: the service provider is discovered and the `Autentique`
facade is available immediately.

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.4.1 to 8.5 |
| Laravel | 13 |
| PHP extensions | `json` and `hash`, enabled in every PHP 8 build; `curl` recommended; and [what Laravel requires](https://laravel.com/docs/13.x/deployment#server-requirements) |
| An Autentique account | with an API token, from the dashboard's API settings |

The HTTP client, Guzzle under Laravel's, sends through `curl` when it is loaded
and through PHP streams otherwise, which need `allow_url_fopen` on and `openssl`
loaded, since Autentique is reached over HTTPS.

Why this floor, and not a lower one, is
[decision 0002](/decisions/0002-php-and-laravel-floor).

## The token

Add it to the application's environment:

```ini
AUTENTIQUE_TOKEN=your-token
```

The token signs documents in the account owner's name, so it belongs in the
environment and nowhere else: not in the repository, not in a log, not in an
exception. The package never writes it to any of those.

## Sandbox while you build

```ini
AUTENTIQUE_SANDBOX=true
```

Sandbox documents are not billed, carry no legal validity, and are deleted by
Autentique after a few days. Set it in local and staging environments, and
leave it unset in production, where a forgotten variable should create real
documents rather than invalid ones
([0006](/decisions/0006-sandbox-is-chosen-per-call)).

## Check that it works

```bash
php artisan autentique:check
```

```
  Endpoint ......................... https://api.autentique.com.br/v2/graphql
  Sandbox by default ............................................... yes
  Token ....................................................... accepted
  Account ..................... Mateus Zanella <mateus@autentique.com.br>
  Organization ............................................... Autentique
  Documents left ..................................................... 20
  Verification credits .............................................. 200
```

It exits with `0` when the token is accepted and `1` otherwise, so a deployment
can run it before anything else. In code, the same question is:

```php
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

$user = Autentique::account()->me();

$user->name;                           // 'Mateus Zanella'
$user->organization?->id;              // 179, what createDocument's organization argument takes
$user->subscription?->documents;       // documents left in the plan
$user->birthday?->toDateString();      // a CarbonImmutable, from Autentique's dd/mm/yyyy
```

Everything else is in [configuration](/guide/configuration).
