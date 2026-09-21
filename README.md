# Laravel Autentique

[![CI](https://github.com/lsnepomuceno/laravel-autentique/actions/workflows/main_action.yml/badge.svg)](https://github.com/lsnepomuceno/laravel-autentique/actions/workflows/main_action.yml)

A Laravel client for the [Autentique](https://www.autentique.com.br) GraphQL
API v2: documents, signers, folders and webhooks.

**Under construction.** Nothing is released yet, and the work towards 1.0.0 is
tracked in [#1](https://github.com/lsnepomuceno/laravel-autentique/issues/1).
It replaces
[`lsnepomuceno/laravel-autentique-v2`](https://github.com/lsnepomuceno/laravel-autentique-v2),
which is no longer maintained.

## Compatibility

| | |
|---|---|
| PHP | 8.4.1 to 8.5 |
| Laravel | 13 |

## Usage

The API surface grows with each issue of
[#1](https://github.com/lsnepomuceno/laravel-autentique/issues/1). What exists
today:

```php
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

// A document, its file and its signers, sent in one call.
$document = Autentique::newDocument('Service agreement')
    ->file($request->file('contract'))            // or a path, or Autentique::fromDisk('s3', 'contracts/1.pdf')
    ->signer(Signer::email('ana@example.com')->withPosition(Position::signature(x: 50, y: 90)))
    ->signer(Signer::whatsapp('+5554999999999', Action::Approve))
    ->reminder(Reminder::Weekly)
    ->send();

$document->signatures[0]->publicId;

// The same call without the builder, and the file sources it accepts.
Autentique::documents()->create($newDocument, $signers, Autentique::fromPath('/tmp/contract.pdf'));
Autentique::fromUpload($request->file('contract'));

// Who the token belongs to, their plan and their organization.
$user = Autentique::account()->me();
$user->subscription?->documents;

// The escape hatch: any GraphQL document, with variables, for what the
// package does not model yet. Returns the response's `data`.
$data = Autentique::query('query { me { id name } }');
```

Every failure is an `LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException`,
narrowed to the fault: `Unauthenticated`, `RateLimited`, `ValidationFailed`,
`NotFound`, `GraphQLError`, `TransportFailed`, `MissingToken`.

## Development

```bash
composer check          # everything CI runs: pint, normalize, phpstan, deps, pest, type coverage
docker compose -f .docker/compose.yaml run --rm php85 composer check
```

## License

MIT. See [LICENSE.md](LICENSE.md).
