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

Everything else is in [configuration](/guide/configuration).
