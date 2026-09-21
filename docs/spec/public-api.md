# Public API

What this package exposes, as it is built today. Everything here is a promise to
consumers: adding to it is a minor release, changing or removing it is a major
one.

This page describes the code on `main`, not the plan. What 1.0.0 will expose is
tracked in #1, and each part is added here by the pull request that ships it.

## Namespace layout

```
src/
├── LaravelAutentiqueServiceProvider.php   # merges the config, binds the contract
├── AutentiqueManager.php                  # the Autentique implementation
├── Contracts/Autentique.php               # this package's surface
└── Facades/Autentique.php
```

The root namespace `LSNepomuceno\LaravelAutentique` is fixed; renaming it would
be a gratuitous break.

## The facade, and the contract behind it

`Contracts\Autentique`, resolved from the container as a singleton or reached
through the `Autentique` facade, which is auto discovered.

It declares no method yet. Every method it gains must appear in the README,
which `tests/Project/ArchTest.php` checks, and in the facade's `@method`
docblock.

The contract is bound rather than the class, so an application can bind its own
implementation in a service provider.

## Configuration

`config/autentique.php`, publishable with the `autentique-config` tag. Every key
is a scalar ([invariant 4](invariants.md)).

| Key | Environment | Default |
|---|---|---|
| `token` | `AUTENTIQUE_TOKEN` | none |
| `url` | `AUTENTIQUE_URL` | `https://api.autentique.com.br/v2/graphql` |
| `sandbox` | `AUTENTIQUE_SANDBOX` | `false` |
| `timeout` | `AUTENTIQUE_TIMEOUT` | `30` seconds |

Adding a key is a minor release. Removing or renaming one is a major release,
because an application's published config file keeps the old name.

## What is not public

- The private methods of the manager and the provider.
- Anything under `tests/`, which never ships.
