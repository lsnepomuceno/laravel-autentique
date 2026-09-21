# Changelog

Every release, and what it costs to move to it.

This file is the summary. The reasoning behind a change lives in
[docs/decisions/](docs/decisions/README.md).

**Semantic versioning, and the public API is what
[docs/spec/public-api.md](docs/spec/public-api.md) says it is.** Adding to it is
a minor release; changing it is a major one.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0] - 2026-09-21

The first stable release. Against `1.0.0-rc.1`, it carries what the first run
against the live API found, with the standard schema now introspected rather
than assembled.

**Corporate and OAuth are experimental.** Neither has run against Autentique:
the Corporate endpoint needs the Corporate plan, and OAuth a registered
application. `Api\Corporate`, `Api\OAuth` and what only they use are outside the
semantic versioning promise until they have, and may change in a minor release
([public API](docs/spec/public-api.md#experimental)).

### Changed

- **Breaking against `1.0.0-rc.1`:** `Api\Folders::create()` no longer takes
  `$type`. The live API's `createFolder` has no such argument, and sending it
  failed.
- `onlySandbox: true` on `documents()->list()` and `folders()->documents()` now
  lists only sandbox documents: Autentique ignores the flag, so the page is
  filtered by the package. The counts stay Autentique's.
- `Api\Corporate::createWebhookEndpoint()` accepts `signature.delivery_failed`
  and `signature.biometric_reset`, which the API's schema lists and the Corporate
  documentation left out.

### Added

- Enum cases the live schema has and the documentation never mentions:
  `DocumentStatus::Rejected`, `EmailTemplateType::SignatureCompleted`,
  `PositionElement::Radio`, `PositionElement::SquareInitials`,
  `VerificationType::PfFacialMatch`, with `SecurityVerification::pfFacialMatch()`.
- `Data\Page::filter()`.
- `Data\User::$group`, the token owner's group, read from `me` because
  Autentique answers an organization's `groups` with null.

### Verified

- Webhooks against real deliveries: the signature, the payload shape (the
  resource at `event.data`), the middleware, the controller and the event.

## [1.0.0-rc.1] - 2026-09-21

The first release of `lsnepomuceno/laravel-autentique`, a new package replacing
`lsnepomuceno/laravel-autentique-v2`. Nothing of the previous package's API
survives; what it was and why it was replaced rather than upgraded is recorded
in [docs/history/from-laravel-autentique-v2.md](docs/history/from-laravel-autentique-v2.md),
and [UPGRADE.md](UPGRADE.md) maps every part of it.

**A release candidate.** Everything planned for 1.0.0 is here, and none of it has
yet run against Autentique itself: the schemas the operations are validated
against are assembled from the documentation, and a run against the sandbox
needs a token this repository never holds. `1.0.0` follows that run, tracked in
[#48](https://github.com/lsnepomuceno/laravel-autentique/issues/48).

### Added

- **Documents**: `Autentique::newDocument()`, a fluent builder sending
  `createDocument` as a GraphQL multipart request, and `Autentique::documents()`
  to find, list, update, block, delete, sign, transfer and move them.
- **Signers**: typed signers reached by email, WhatsApp, SMS or link, with
  positions, security verifications and a CPF; on an existing document,
  `Autentique::signers()` adds, removes, resends, creates links and approves or
  rejects biometric checks.
- **Folders**: create, rename, delete, share with people, groups or a link, and
  list their documents.
- **Organizations** and **email templates**.
- **Webhooks**: verified on the raw body with HMAC SHA-256 before anything runs,
  both payload shapes read, dispatched as `AutentiqueWebhookReceived`, with an
  opt-in guard against duplicates.
- **Corporate**: child organizations, their members and plans, login codes,
  webhook endpoints, custom plans and API usage, on the Corporate endpoint.
- **OAuth 2.0** with PKCE, and `Autentique::withToken()` for any other token.
- **Files** from a path, an upload or a `Storage` disk, streamed in every case.
- **Errors** as one exception per fault under `AutentiqueException`; Autentique's
  documented codes as an enum, in English and Brazilian Portuguese.
- **Testing**: `Autentique::fake()`, answering every operation from what was
  sent, with assertions, and `FakeWebhook` for signed webhook requests.
- **Commands**: `autentique:check` and `autentique:schema`.
- `Autentique::query()`, the escape hatch for anything the package does not
  model.

Every operation is a `.graphql` file sent with variables only, and the suite
validates each one against the API's schema.
