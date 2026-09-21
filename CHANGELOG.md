# Changelog

Every release, and what it costs to move to it.

This file is the summary. The reasoning behind a change lives in
[docs/decisions/](docs/decisions/README.md).

**Semantic versioning, and the public API is what
[docs/spec/public-api.md](docs/spec/public-api.md) says it is.** Adding to it is
a minor release; changing it is a major one.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

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
