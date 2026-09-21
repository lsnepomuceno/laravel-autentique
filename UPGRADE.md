# Upgrading

## From `lsnepomuceno/laravel-autentique-v2`

`lsnepomuceno/laravel-autentique` is a new package, not a new major of the old
one: nothing of its API survives, not a class, a method, a return type or the
namespace. Why it was replaced rather than upgraded is in
[the history](docs/history/from-laravel-autentique-v2.md).

### Installing

```bash
composer remove lsnepomuceno/laravel-autentique-v2
composer require lsnepomuceno/laravel-autentique
```

| | v2 | now |
|---|---|---|
| PHP | 7.4, 8.0 | 8.4.1 to 8.5 |
| Laravel | 8 | 13 |
| Namespace | `LSNepomuceno\LaravelAutentiqueV2` | `LSNepomuceno\LaravelAutentique` |
| Entry point | `new Document()`, `new Folder()` | the `Autentique` facade, or `Contracts\Autentique` injected |

### Configuration

| v2 | now |
|---|---|
| `config('services.autentique.token')`, added to `config/services.php` by hand | `AUTENTIQUE_TOKEN`, read into `autentique.token`; nothing to add |
| `new Document(true)` for sandbox | `->sandbox()` per call, or `AUTENTIQUE_SANDBOX=true` as the default |

Remove the `autentique` entry from `config/services.php`.

### Documents

| v2 | now |
|---|---|
| `(new Document)->create($name, $path, $deleteLocalFile, $signers)` | `Autentique::newDocument($name)->file($path)->signer(...)->send()`; deleting the local file is the application's |
| `(new Document)->read($id)` | `Autentique::documents()->find($id)` |
| `(new Document)->readAll($perPage, $page)` | `Autentique::documents()->list(page: $page, perPage: $perPage)` |
| `(new Document)->delete($id)` | `Autentique::documents()->delete($id)`; it does not stop signing, `block($id)` does |
| `(new Document)->sign($id)` | `Autentique::documents()->sign($id)` |
| `(new Document)->changeFolder($id, $folderId)` | `Autentique::documents()->moveToFolder($id, $folderId)`; it never worked in v2 |

### Signers

v2's `Signers` builder becomes one immutable `Data\Input\Signer` per signer:

```php
// v2
$signers = (new Signers)->email('ana@example.com')->x(50)->y(90)->z(1)->setSigner();

// now
Signer::email('ana@example.com')->withPosition(Position::signature(x: 50, y: 90, page: 1));
```

| v2 | now |
|---|---|
| `email($email)` then `setSigner()` | `Signer::email($email)`; nothing to commit, and the last signer is no longer lost |
| `name($name)` | `Signer::link($name)`, or `->withName($name)` |
| `x()`, `y()`, `z()`, `setPosition()`, `setFullSigner()` | `->withPosition(Position::signature($x, $y, $page))`, once per position |
| every signer's action hard coded to `SIGN` | the second argument: `Signer::email($email, Action::Approve)` |
| `getSigners()` | a `list<Signer>` given to `signers()` or `create()` |

### Folders

| v2 | now |
|---|---|
| `(new Folder)->create($name)` | `Autentique::folders()->create($name)` |
| `(new Folder)->read($id)` | `Autentique::folders()->find($id)` |
| `(new Folder)->readAll()` | `Autentique::folders()->list()` |
| `(new Folder)->delete($id)` | `Autentique::folders()->delete($id)` |
| `(new Folder)->documentsByFolder($id, $perPage, $page)` | `Autentique::folders()->documents($id, page: $page, perPage: $perPage)` |

### Answers

v2 returned `Illuminate\Support\Fluent` over the decoded response, so code read
`$response->data->createDocument->id`. Every method now returns a typed value
object, or a `bool` for a confirmation:

```php
// v2
$id = $response->data->createDocument->id;
$link = $response->data->createDocument->signatures[0]->link->short_link;

// now
$id = $document->id;
$link = $document->signatures[0]->link?->shortLink;
```

Properties are camel case and dates are `CarbonImmutable`.

### Errors

| v2 | now |
|---|---|
| `AutentiqueResponseException`, carrying the first error's message | one exception per fault, all extending `Exceptions\AutentiqueException`: `ValidationFailed`, `NotFound`, `RateLimited`, `Unauthenticated`, … |
| `AutentiqueTokenNotFoundException`, never thrown | `Exceptions\MissingToken`, thrown before anything is sent |
| `InvalidSignaturePositionException`, never used | `Exceptions\InvalidInput`, for a position off the page among others |

### The transport

v2's `Autentique` class, extending the `Http` facade, and its `runJson()`,
`performPost()`, `setParams()`, `setPostType()` and `setDeleteLocalFile()`, have
no replacement: the package sends every request itself. For an operation it
does not model, `Autentique::query($graphql, $variables)` sends it.

### Tests

v2's tests reached the live API. Now:

```php
$autentique = Autentique::fake();

// … the application runs …

$autentique->assertDocumentSent();
```

## Unreleased

### From `1.0.0-rc.1`

`Autentique::folders()->create($name, $parentId, $type)` loses its third
argument: the live API has no such argument. Drop it; a folder shared with the
organization or a group is shared after it is created, with `share()`.
