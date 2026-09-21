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
