# Documents

## Creating one

A document is a file, a name and at least one signer. Autentique emails, texts
or messages each signer as it is created.

```php
use LSNepomuceno\LaravelAutentique\Data\Input\{Position, Signer};
use LSNepomuceno\LaravelAutentique\Enums\{Action, Reminder};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

$document = Autentique::newDocument('Service agreement')
    ->file($request->file('contract'))
    ->signer(Signer::email('ana@example.com')->withPosition(Position::signature(x: 50, y: 90)))
    ->signer(Signer::whatsapp('+5554999999999', Action::Approve))
    ->reminder(Reminder::Weekly)
    ->send();
```

`send()` returns a `Data\Document`. Keep its `$id`: every later call about the
document takes it. Each signer's `$publicId` is in `$document->signatures`.

## The file

`file()` takes whatever the file is at hand:

| Given | Sent |
|---|---|
| a path, `'/tmp/contract.pdf'` | streamed from the local filesystem |
| an `UploadedFile`, `$request->file('contract')` | streamed from the upload, without storing it first |
| `Autentique::fromDisk('s3', 'contracts/1.pdf')` | streamed from any `Storage` disk, never copied to a local file |
| `Autentique::fromPath($path, 'Name.pdf')`, `Autentique::fromUpload($file, 'Name.pdf')` | as above, under another name |

The file's extension is how Autentique tells a PDF from HTML or Markdown. PDF is
the norm; HTML works too, which is the documented way to fill a template
yourself, since dashboard templates cannot be used through the API. The size
limit is 5 MB on the Free plan and 20 MB on the Professional plan, and
Autentique enforces it.

A missing path, a failed upload or a missing disk file is refused with
`InvalidInput` before anything is sent.

## The options

Every option is a method on the builder, and every one left out is Autentique's
default.

| Method | Effect |
|---|---|
| `message($text)` | the message in the request email |
| `reminder(Reminder::Daily \| Weekly)` | reminds signers who have not acted, up to 7 times daily or 4 weekly, by email only. It makes the document refusable |
| `refusable()` | signers may refuse |
| `stopOnRejected()` | once someone refuses, nobody else can sign. Needs `refusable()` |
| `sortable()` | signers sign in the order they were added |
| `qualified()` | every signer signs with an ICP-Brasil certificate |
| `scrollingRequired()` | signers must scroll through the whole document |
| `footer(Footer::Bottom, FooterType::Mini)` | the signature footer on each page |
| `newSignatureStyle()`, `withoutAuditPage()` | the new signature fields; the audit page can only be left out with them |
| `ignoreCpf()`, `ignoreBirthdate()` | nobody is asked for them |
| `emailTemplate($id)` | one of the account's email templates |
| `deadline($moment)` | nobody can sign after it |
| `expiration(new Expiration($date, daysBefore: 7))` | a reminder ahead of a due date; it blocks nothing |
| `replyTo($email)` | where replies to the request go |
| `watchers([...])` | people who follow without signing, Professional plan |
| `cc([...])` | people who receive the signed document |
| `configs(new DocumentConfig(...))` | notifications, signature appearance, PDF/A, locked user data |
| `locale(new Locale('US', Language::EnglishUnitedStates))` | country, language, timezone, date format |
| `whatsappTemplate(WhatsappTemplate::Formal)` | the wording of WhatsApp messages |
| `sandbox()` | a test document; see [sandbox](/guide/sandbox) |
| `inFolder($folderId)`, `forOrganization($id)` | where it is created |
| `whatsappFlow()` | signed inside WhatsApp, from a Markdown file |

## What is refused before sending

Autentique documents some combinations as refused, and others as silently
changed. Both are refused here with `InvalidInput`, before a request is spent:

- a reminder on a document explicitly not refusable;
- `stopOnRejected()` without `refusable()`;
- `withoutAuditPage()` without `newSignatureStyle()`;
- PDF/A with unlocked user data or the old signature style;
- a WhatsApp Flow document that is not Markdown, and Markdown that is not a
  WhatsApp Flow document;
- no file, or no signer.

## Without the builder

The builder makes one call, which can be made directly:

```php
use LSNepomuceno\LaravelAutentique\Data\Input\NewDocument;

$document = Autentique::documents()->create(
    new NewDocument('Service agreement', refusable: true, reminder: Reminder::Weekly),
    [Signer::email('ana@example.com')],
    Autentique::fromPath('/tmp/contract.pdf'),
    sandbox: true,
);
```

## What comes back

`Data\Document` carries the settings as Autentique holds them, `$files` with the
download links (`original`, `signed`, `certified`, `pades`), and one
`Data\Signature` per signer, with their link, their account, what happened to
their email, their positions, their verifications, and every event: viewed,
signed, rejected, and the biometric approvals. Every date is a
`CarbonImmutable`.

```php
$document->isSigned();                               // every signer has signed
$document->signature($publicId)?->signed?->at;       // when that one signed
$document->signatures[1]->link?->shortLink;          // the link to deliver
```

## Reading one

```php
$document = Autentique::documents()->find($id);
```

**Every document Autentique returns is billed**, one at a time, whether it is
read here or in a listing. A document that does not exist, or that the token's
owner cannot see, throws `NotFound`.

## Listing

```php
use LSNepomuceno\LaravelAutentique\Enums\DocumentStatus;

$page = Autentique::documents()->list(status: DocumentStatus::Pending, search: 'agreement');

foreach ($page as $document) {
    // …
}

$page->hasMorePages;     // loop on this, with page: 2, 3, …
$page->total;            // when Autentique reports it
```

The page holds 20 documents unless `perPage:` says otherwise, because each one
is billed. Every filter the API takes is a named argument:

| Argument | Filters by |
|---|---|
| `status` | `Pending`, `Signed`, `NotSigned`, `Deleted` |
| `search`, `name`, `signer` | the document's name and its signers |
| `folderId`, `context` | a folder, or the user's, group's or organization's documents |
| `from`, `until` | the creation date, inclusive, in the account's timezone |
| `includeDeleted`, `includeArchived` | documents normally left out |
| `orderBy`, `direction` | any field, ascending or descending |
| `sandbox`, `onlySandbox` | see below |

**Sandbox documents are hidden by Autentique unless asked for.** With
`AUTENTIQUE_SANDBOX=true` they are included by default, as they are created by
default; `sandbox: false` leaves them out, and `onlySandbox: true` lists nothing
else.

Autentique documents an `onlySandbox` argument and does not honour it: measured
against the live API, it filters nothing. The package asks for sandbox documents
and filters the page itself, so `onlySandbox: true` returns only sandbox
documents, and the page's `total` and `lastPage` stay Autentique's, counting the
documents it paged. A page can then hold fewer items than `perPage`, or none,
with more pages after it.

## Changing one

```php
use LSNepomuceno\LaravelAutentique\Data\Input\DocumentChanges;

$document = Autentique::documents()->update($id, new DocumentChanges(
    name: 'Service agreement, revised',
    reminder: Reminder::Daily,
    watchers: ['legal@example.com'],
));
```

Only what is set is sent, and it returns the document as it is now. The rules
that apply on creation apply to what is set together. Signers are added and
removed separately, in [signers](/guide/signers).

## Stopping anyone from signing

```php
Autentique::documents()->block($id);
```

It moves the deadline to now. **Deleting does not do this.**

## Deleting

```php
Autentique::documents()->delete($id);                 // true when Autentique did it
Autentique::documents()->delete($id, $folderId);      // and every signature filed in that folder
```

It works as the dashboard's delete: the document goes to the trash, and if
anyone has already signed, only the caller's copy goes. **Whoever still has the
link can sign it**; `block()` first if that matters.

## Signing it yourself

```php
Autentique::documents()->sign($id);
```

Signs as the token's owner, who has to be one of the document's signers:
otherwise it fails with `signature_not_found`. It never signs on anyone
else's behalf.

## Moving it

```php
Autentique::documents()->moveToFolder($id, $folderId);
Autentique::documents()->moveToFolder($id, $newFolderId, currentFolderId: $oldFolderId);
Autentique::documents()->moveToFolder($id, null);                             // out of every folder
Autentique::documents()->transfer($id, organizationId: 179, groupId: 7);     // to another organization's group
```

