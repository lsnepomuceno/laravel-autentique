# Laravel Autentique

[![CI](https://github.com/lsnepomuceno/laravel-autentique/actions/workflows/main_action.yml/badge.svg)](https://github.com/lsnepomuceno/laravel-autentique/actions/workflows/main_action.yml)

A Laravel client for the [Autentique](https://www.autentique.com.br) GraphQL
API v2: electronic signatures, documents, signers, folders, organizations,
webhooks, the Corporate endpoint and OAuth.

- **Every operation is a `.graphql` file** in the package, sent with variables
  and validated against the API's schema, never a string built at runtime.
- **Typed in, typed out.** Signers, positions and verifications are built as
  value objects, and every answer comes back as one, with enums and dates.
- **Refused before it is spent.** What Autentique documents it would refuse, or
  silently change, is refused before a request.
- **One transport, Laravel's HTTP client**, so `Http::fake()` reaches every
  request, and `Autentique::fake()` makes testing your own code trivial.

**Documentation: [lsnepomuceno.github.io/laravel-autentique](https://lsnepomuceno.github.io/laravel-autentique/)**

It replaces
[`lsnepomuceno/laravel-autentique-v2`](https://github.com/lsnepomuceno/laravel-autentique-v2);
[UPGRADE.md](UPGRADE.md) maps every part of it.

## Compatibility

| | |
|---|---|
| PHP | 8.4.1 to 8.5 |
| Laravel | 13 |

## Installing

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

## Contributing

`composer check` runs everything CI runs: Pint, composer normalize, PHPStan at
level max, the dependency analyser, the suite and 100% type coverage. See
[CONTRIBUTING.md](CONTRIBUTING.md), and [SECURITY.md](SECURITY.md) for
reporting a vulnerability.

```bash
docker compose -f .docker/compose.yaml run --rm php85 composer check
```

## License

MIT. See [LICENSE.md](LICENSE.md).
