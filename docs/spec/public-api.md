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
├── Facades/Autentique.php
├── Exceptions/                            # AutentiqueException and what extends it
├── GraphQL/                               # Operation, Endpoint, OperationLoader
└── Resources/graphql/                     # one .graphql file per operation
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

## Operations

`GraphQL\Operation` names every operation the package sends, one case per file
under `src/Resources/graphql/`. It is public because tests and the fake name
operations by it. Adding a case is a minor release; removing or renaming one is
a major release.

The selection of fields inside each file is not itself public: what is public is
the value object built from it
([0008](../decisions/0008-responses-are-typed-value-objects.md)).

## Exceptions

Everything the package throws extends `Exceptions\AutentiqueException`, which
is abstract.

| Exception | When |
|---|---|
| `InvalidOperation` | an operation file is missing or spreads a fragment no file defines. A defect in the package, which the suite exists to prevent |

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
