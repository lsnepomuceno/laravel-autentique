<h1 align="center">Laravel Autentique</h1>

<p align="center">
  A Laravel client for the <a href="https://www.autentique.com.br">Autentique</a> GraphQL API v2: electronic signatures,
  <br>documents, signers, folders, organizations, webhooks, the Corporate endpoint and OAuth.
  <br><br>Every operation is a <code>.graphql</code> file validated against the API's schema, every answer a typed value object,
  <br>and every request goes through Laravel's HTTP client, so <code>Http::fake()</code> and <code>Autentique::fake()</code> reach it.
</p>

<p align="center">
  <a href="https://packagist.org/packages/lsnepomuceno/laravel-autentique"><img alt="Latest version" src="https://img.shields.io/packagist/v/lsnepomuceno/laravel-autentique?style=flat-square&color=1f7a3d&label=packagist"></a>
  <a href="https://packagist.org/packages/lsnepomuceno/laravel-autentique/stats"><img alt="Downloads" src="https://img.shields.io/packagist/dt/lsnepomuceno/laravel-autentique?style=flat-square&color=1f7a3d"></a>
  <a href="https://github.com/lsnepomuceno/laravel-autentique/actions/workflows/main_action.yml"><img alt="Tests" src="https://img.shields.io/github/actions/workflow/status/lsnepomuceno/laravel-autentique/main_action.yml?branch=main&style=flat-square&label=tests"></a>
  <a href="https://github.com/lsnepomuceno/laravel-autentique/blob/main/LICENSE.md"><img alt="License" src="https://img.shields.io/packagist/l/lsnepomuceno/laravel-autentique?style=flat-square&color=555"></a>
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/php-8.4.1%20%E2%80%93%208.5-777bb4?style=flat-square&logo=php&logoColor=white">
  <img alt="Laravel" src="https://img.shields.io/badge/laravel-13-ff2d20?style=flat-square&logo=laravel&logoColor=white">
  <img alt="Autentique API" src="https://img.shields.io/badge/autentique%20api-v2-1f7a3d?style=flat-square">
  <img alt="PHPStan" src="https://img.shields.io/badge/phpstan-level%20max-2a2a2a?style=flat-square">
  <img alt="Type coverage" src="https://img.shields.io/badge/type%20coverage-100%25-1f7a3d?style=flat-square">
</p>

<p align="center">
  <a href="https://lsnepomuceno.github.io/laravel-autentique/"><b>Documentation</b></a>
  &nbsp;·&nbsp;
  <a href="https://lsnepomuceno.github.io/laravel-autentique/guide/getting-started">Getting started</a>
  &nbsp;·&nbsp;
  <a href="https://lsnepomuceno.github.io/laravel-autentique/spec/public-api">Public API</a>
  &nbsp;·&nbsp;
  <a href="UPGRADE.md">Upgrading</a>
  &nbsp;·&nbsp;
  <a href="CHANGELOG.md">Changelog</a>
</p>

---

## What it does

- **Every operation is a `.graphql` file** in the package, sent with variables
  and validated against the API's schema, never a string built at runtime.
- **Typed in, typed out.** Signers, positions and verifications are built as
  value objects, and every answer comes back as one, with enums and dates.
- **Refused before it is spent.** What Autentique documents it would refuse, or
  silently change, is refused before a request.
- **One transport, Laravel's HTTP client**, so `Http::fake()` reaches every
  request, and `Autentique::fake()` makes testing your own code trivial.
- **Webhooks verified** against `X-Autentique-Signature` on the raw body, and
  delivered as a Laravel event.

It replaces
[`lsnepomuceno/laravel-autentique-v2`](https://github.com/lsnepomuceno/laravel-autentique-v2),
unfinished since 2021; [UPGRADE.md](UPGRADE.md) maps every part of it.

## Installation

```bash
composer require lsnepomuceno/laravel-autentique
```

```ini
AUTENTIQUE_TOKEN=your-api-token
AUTENTIQUE_SANDBOX=true          # local and staging only
```

```bash
php artisan autentique:check     # is the token accepted, and whose account is it
```

Every key has an environment variable and a default, so publishing
`config/autentique.php` is optional:

```bash
php artisan vendor:publish --tag=autentique-config
```

## Documents

```php
use LSNepomuceno\LaravelAutentique\Data\Input\{Position, Signer};
use LSNepomuceno\LaravelAutentique\Enums\{Action, Reminder};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

$document = Autentique::newDocument('Service agreement')
    ->file($request->file('contract'))            // or a path, or Autentique::fromDisk('s3', 'contracts/1.pdf')
    ->signer(Signer::email('ana@example.com')->withPosition(Position::signature(x: 50, y: 90)))
    ->signer(Signer::whatsapp('+5554999999999', Action::Approve))
    ->reminder(Reminder::Weekly)
    ->send();

$document->id;
$document->signatures[0]->publicId;
```

The file can be a path, an upload, `Autentique::fromPath()`,
`Autentique::fromUpload()` or `Autentique::fromDisk()`, and it is streamed in
every case. The same call without the builder:

```php
Autentique::documents()->create($newDocument, $signers, Autentique::fromPath('/tmp/contract.pdf'));
```

Everything else about a document:

```php
Autentique::documents()->find($id);
Autentique::documents()->list(status: DocumentStatus::Pending);   // Data\Page<Document>
Autentique::documents()->update($id, new DocumentChanges(name: 'Revised'));
Autentique::documents()->block($id);                              // nobody can sign from now on
Autentique::documents()->delete($id);                             // the trash; it does not stop signing
Autentique::documents()->moveToFolder($id, $folderId);
```

## Signers of an existing document

```php
Autentique::signers()->add($documentId, Signer::email('late@example.com'));
Autentique::signers()->resend([$publicId]);
Autentique::signers()->link($publicId)->shortLink;
Autentique::signers()->approveBiometric($verificationId, $publicId);
```

## Folders and organizations

```php
$folder = Autentique::folders()->create('Contracts 2026');
Autentique::folders()->share($folder->id, [Share::withEmail('legal@example.com', FolderRole::Editor)]);
Autentique::folders()->documents($folder->id);

Autentique::organizations()->current()->groups;
Autentique::organizations()->emailTemplates();
Autentique::account()->me()->subscription?->documents;
```

## Webhooks

```ini
AUTENTIQUE_WEBHOOK_SECRET=the-endpoint-secret
AUTENTIQUE_WEBHOOK_PATH=webhooks/autentique
```

```php
Event::listen(function (AutentiqueWebhookReceived $received) {
    if ($received->event->type === WebhookEventType::DocumentFinished) {
        // $received->event->documentId()
    }
});
```

Every webhook is verified against `X-Autentique-Signature` on the raw body
before anything runs.

## Corporate and OAuth

**Experimental:** neither has run against Autentique yet, so both may change in a
minor release until they have
([public API](https://lsnepomuceno.github.io/laravel-autentique/spec/public-api#experimental)).

```php
// The Corporate plan: child organizations, members, plans, webhook endpoints.
$child = Autentique::corporate()->createOrganization(new NewChildOrganization(name: 'Branch office'));

// OAuth: act on behalf of accounts that authorize the application.
$authorization = Autentique::oauth()->begin([OAuthScope::UserRead, OAuthScope::DocumentsCreate]);
$tokens = Autentique::oauth()->callback($request, $state, $verifier);

Autentique::withToken($tokens->accessToken)->account()->me();
```

## Errors

Every failure is an `LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException`,
narrowed to the fault: `ValidationFailed` with its codes per field, `NotFound`,
`RateLimited` with the wait Autentique asked for, `Unauthenticated`,
`InsufficientScope`, `TransportFailed`, `InvalidInput` for what was refused
before sending, and a few more. Autentique reports most errors with HTTP 200;
the package reads them for you.

## Testing

```php
$autentique = Autentique::fake();

// … the application runs …

$autentique->assertDocumentSent(fn(array $document) => $document['name'] === 'Service agreement');
```

Nothing is sent, no token is needed, and every answer is built from what was
sent.

## Anything the package does not model

```php
$data = Autentique::query('query ($id: UUID!) { document(id: $id) { hashes { sha2 } } }', ['id' => $id]);
```

## Compatibility

| Requirement | Version |
|---|---|
| PHP | 8.4.1 to 8.5 |
| Laravel | 13 |
| PHP extensions | `json`, plus what Laravel itself requires; `curl` recommended |

`json` and `hash` (for the webhook signature) ship enabled in every PHP 8
build. The HTTP client, Guzzle under Laravel's, uses `curl` when it is loaded
and PHP streams otherwise, which need `allow_url_fopen` on and `openssl`
loaded, since Autentique is reached over HTTPS.

`lsnepomuceno/laravel-autentique-v2`, for Laravel 8 and PHP 7.4 to 8.0, is no
longer maintained; [UPGRADE.md](UPGRADE.md) maps it to this package.

## Verified against Autentique

The suite never reaches Autentique, and needs no token. What it is checked
against was taken from the live API:

- **The schema.** `tests/Resources/schema.graphql` is introspected from the
  standard endpoint, and every operation file is validated against it, as are
  the examples of Autentique's own Postman and Altair collections.
- **A sandbox run** of documents, signers, folders, organizations and the
  account, which corrected one signature and five enums the documentation had
  wrong.
- **Real webhook deliveries**, whose signatures verified and whose payload is a
  fixture of the suite.

The Corporate endpoint and OAuth are the exception, and are experimental until
they run too.

## Contributing

Patches are expected to come with tests. `composer check` runs everything CI
runs: Pint, composer normalize, PHPStan at level max with no baseline, the
dependency analyser, the suite and 100% type coverage.

```bash
docker compose -f .docker/compose.yaml run --rm php85 composer check
```

See [CONTRIBUTING.md](CONTRIBUTING.md), and [ARCHITECTURE.md](ARCHITECTURE.md)
for how the package is put together and why. The rules that break the product
when violated are in [docs/spec/invariants.md](docs/spec/invariants.md), and the
reasoning behind the design is one numbered file per decision in
[docs/decisions/](docs/decisions/README.md).

## Security

Found a vulnerability? Please follow [SECURITY.md](SECURITY.md) rather than
opening a public issue.

## License

MIT. See [LICENSE.md](LICENSE.md).
