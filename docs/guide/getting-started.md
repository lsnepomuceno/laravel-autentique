# Getting started

::: warning Not released yet
The package is being built towards 1.0.0, tracked in
[#1](https://github.com/lsnepomuceno/laravel-autentique/issues/1). This page
describes what is on `main` today.
:::

```bash
composer require lsnepomuceno/laravel-autentique
```

Nothing to register: the service provider is discovered and the `Autentique`
facade is available immediately.

## Requirements

| | |
|---|---|
| PHP | 8.4.1 to 8.5 |
| Laravel | 13 |
| Extensions | `ext-json` |
| An Autentique account | with an API token, from the dashboard's API settings |

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
