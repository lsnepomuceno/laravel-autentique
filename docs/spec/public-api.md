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
├── Contracts/                             # Autentique, GraphQLClient, FileSource
├── Api/                                   # one class per area of the API: Account, Documents, PendingDocument, Signers
├── Commands/                              # CheckCommand, SchemaCommand
├── Facades/Autentique.php
├── Data/                                  # value objects returned: Document, Signature, User, …
│   └── Input/                             # value objects sent: NewDocument, Signer, Position, …
├── Enums/                                 # every closed set of API values, and ErrorCode
├── Io/                                    # PathFile, UploadedFileSource, DiskFile
├── Exceptions/                            # AutentiqueException and what extends it
├── GraphQL/                               # Client, ResponseParser, Operation, Endpoint, OperationLoader
└── Resources/graphql/                     # one .graphql file per operation

lang/{en,pt_BR}/errors.php                 # the text of every ErrorCode
```

The root namespace `LSNepomuceno\LaravelAutentique` is fixed; renaming it would
be a gratuitous break.

## The facade, and the contract behind it

`Contracts\Autentique`, resolved from the container as a singleton or reached
through the `Autentique` facade, which is auto discovered.

| Method | Returns | Notes |
|---|---|---|
| `account()` | `Api\Account` | `me()` returns `Data\User` |
| `documents()` | `Api\Documents` | `create()`, `find()`, `update()`, `block()` return `Data\Document`; `list()` returns `Data\Page<Document>`; `delete()`, `sign()`, `transfer()`, `moveToFolder()` return `bool` |
| `signers()` | `Api\Signers` | `add()`, `approveBiometric()`, `rejectBiometric()` return `Data\Signature`; `link()` returns `Data\Link`; `remove()`, `resend()` return `bool` |
| `newDocument($name)` | `Api\PendingDocument` | the builder; `send()` returns `Data\Document` |
| `fromPath($path, ?$name)`, `fromUpload($file, ?$name)`, `fromDisk($disk, $path, ?$name)` | `Contracts\FileSource` | **Laravel only**: uploads and disks stream |
| `query($graphql, $variables)` | `array<string, mixed>`, the response's `data` | the escape hatch; values as variables, the document is the caller's |

Every method must appear in the README, which `tests/Project/ArchTest.php`
checks, and in the facade's `@method` docblock.

## The transport

`Contracts\GraphQLClient` is bound to `GraphQL\Client`, the only class that
reaches the network ([invariant 1](invariants.md)). It is a contract so the fake
can stand in for it; an application may bind its own, at the cost of
`Http::fake()` no longer reaching it.

`Contracts\FileSource` is what an upload reads: a name and contents, a string
or a stream.

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

## Value objects

Every answer is a `final readonly` class under `Data\`, built from the fields its
operation selects ([0008](../decisions/0008-responses-are-typed-value-objects.md)).
Property names are camel case (`$hasPremiumFeatures` for
`has_premium_features`), dates are `CarbonImmutable`, and a field the API may
omit is nullable.

| Class | Built from |
|---|---|
| `Data\User` | `me` |
| `Data\Subscription`, `Data\Organization`, `Data\Group` | nested in the above, and in `organization` |
| `Data\Document` | `createDocument` and every operation returning a document |
| `Data\Page<T>` | every listing; countable and iterable |
| `Data\Signature`, `Data\Link`, `Data\Files`, `Data\Event`, `Data\Geolocation`, `Data\EmailEvents`, `Data\SignaturePosition`, `Data\Verification` | nested in a document |

An enum value the API returns and the package does not know yet becomes `null`
rather than an exception, so a new value Autentique adds breaks nothing.

## Inputs

What is sent is a `final readonly` class under `Data\Input\`: `NewDocument`,
`DocumentChanges`, `Signer`, `Position`, `SecurityVerification`, `Locale`, `DocumentConfig`,
`Expiration`. Each refuses, with `InvalidInput`, a value Autentique documents it
would refuse or silently change. `toArray()` returns what is sent, with every
unset option left out so Autentique's default applies.

`Api\PendingDocument` is the one mutable class: a builder, whose methods
return the same instance.

Adding a property is a minor release; removing one, or making a nullable one
required, is a major release.

`Support\Payload` is how they read an answer. It is not public.

## Exceptions

Everything the package throws extends `Exceptions\AutentiqueException`, which
is abstract.

| Exception | When |
|---|---|
| `RequestFailed` (abstract) | base of the seven below; carries `$operation`, `$status`, `$requestId`, `$errors` |
| `Unauthenticated` | HTTP 401, or `Unauthorized` with 200 |
| `RateLimited` | HTTP 429 after the retries; carries `$retryAfter` |
| `ValidationFailed` | `message: "validation"`; `violations()`, `messages()` |
| `NotFound` | a `*_not_found` code, or a `… not found` message |
| `ResendThrottled` | `too_many_resent_emails` on `resendSignatures`; wraps the `GraphQLError` |
| `GraphQLError` | any other error, or no data |
| `TransportFailed` | the connection failed, or the answer was not GraphQL |
| `MissingToken` | no token configured, nothing sent |
| `InvalidOperation` | an operation file is missing or spreads a fragment no file defines. A defect in the package, which the suite exists to prevent |
| `UnexpectedResponse` | an answer without a field the package requires. The API and the package disagree about the schema |
| `InvalidInput` | a value refused before sending |

`Enums\ErrorCode` lists every code Autentique documents; `Data\ApiError` and
`Data\Violation` carry what arrived. Adding a case is a minor release.

## Commands

| Command | Exit codes |
|---|---|
| `autentique:check` | `0` token accepted, `1` otherwise |
| `autentique:schema {--output=}` | `0` written, `1` refused |

Their names and exit codes are public: a pipeline calls them.

## Configuration

`config/autentique.php`, publishable with the `autentique-config` tag. Every key
is a scalar ([invariant 4](invariants.md)).

| Key | Environment | Default |
|---|---|---|
| `token` | `AUTENTIQUE_TOKEN` | none |
| `url` | `AUTENTIQUE_URL` | `https://api.autentique.com.br/v2/graphql` |
| `sandbox` | `AUTENTIQUE_SANDBOX` | `false` |
| `timeout` | `AUTENTIQUE_TIMEOUT` | `30` seconds |
| `retry.times` | `AUTENTIQUE_RETRY_TIMES` | `2` |
| `retry.sleep` | `AUTENTIQUE_RETRY_SLEEP` | `1000` milliseconds |

Adding a key is a minor release. Removing or renaming one is a major release,
because an application's published config file keeps the old name.

## What is not public

- The private methods of the manager and the provider.
- Anything under `tests/`, which never ships.
