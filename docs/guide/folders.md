# Folders

```php
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

$folders = Autentique::folders();
```

## Creating and organizing

```php
$contracts = $folders->create('Contracts');
$signed = $folders->create('Signed', parentId: $contracts->id);

$folders->rename($signed->id, 'Signed contracts');
$folders->delete($signed->id);        // the documents in it stay, in no folder
```

A name has **at least three characters**, refused here otherwise. A folder holds
**at most five subfolders**, refused by Autentique as a `ValidationFailed`.

Documents are filed as they are created, with
`Autentique::newDocument(...)->inFolder($folderId)`, or moved later with
[`documents()->moveToFolder()`](/guide/documents#moving-it).

## Reading

```php
$folder = $folders->find($id);

$folder->parent?->name;
$folder->subfolders;     // list<FolderSummary>
$folder->shares;         // list<FolderShare>, each with its role and its user or group

$page = $folders->list(search: 'contracts');                  // Data\Page<Folder>
$page = $folders->list(type: FolderType::Group, withChildren: false);
```

## The documents in a folder

```php
$page = $folders->documents($folderId, status: DocumentStatus::Pending);
```

The same page size, billing and sandbox rules as
[listing documents](/guide/documents#listing).

**A document moved into a folder takes a second or two to be listed in it**:
Autentique's folder listing is eventually consistent. A test that moves and then
lists at once finds nothing; `documents()->find()` sees the move immediately.

## Sharing

```php
use LSNepomuceno\LaravelAutentique\Data\Input\Share;
use LSNepomuceno\LaravelAutentique\Enums\FolderRole;

$folders->share($folderId, [
    Share::withEmail('user@example.com'),                        // a viewer, by default
    Share::withGroup('abc123', FolderRole::Editor),
], message: "Now I'm sharing this folder with you!");

$folders->changeRole($folderId, $share->id, FolderRole::Editor);

$folders->shareByLink($folderId);                                // anyone with the link
$folders->shareByLink($folderId, password: $password);           // behind a password
$folders->removeLinkPassword($folderId);
$folders->stopSharingByLink($folderId);
```

A viewer sees the folder and its documents; an editor also changes and manages
them. Every call returns the folder as it is afterwards, with its shares.
