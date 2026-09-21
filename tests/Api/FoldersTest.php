<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Data\{Document, Folder};
use LSNepomuceno\LaravelAutentique\Data\Input\Share;
use LSNepomuceno\LaravelAutentique\Enums\{Context, DocumentStatus, FolderRole, FolderType};
use LSNepomuceno\LaravelAutentique\Exceptions\{InvalidInput, ValidationFailed};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(fn() => config()->set('autentique.token', 'the-token'));

it('finds a folder with its parent, subfolders and shares', function () {
    answerWith(Operation::Folder, 'folder');

    $folder = Autentique::folders()->find('c8e96bce-fb65-4f1b-a97d-29df0e8cde94');

    expect($folder)->toBeInstanceOf(Folder::class)
        ->and($folder->name)->toBe('Signed contracts')
        ->and($folder->context)->toBe(Context::Organization)
        ->and($folder->childrenCount)->toBe(1)
        ->and($folder->parent?->name)->toBe('Contracts')
        ->and($folder->subfolders[0]->path)->toBe('/Contracts/Signed contracts/2026')
        ->and($folder->shares[0]->role)->toBe(FolderRole::Editor)
        ->and($folder->shares[0]->user?->email)->toBe('legal@example.com')
        ->and($folder->shares[1]->group?->name)->toBe('Legal')
        ->and($folder->shares[1]->user)->toBeNull()
        ->and($folder->hasPassword)->toBeFalse()
        ->and(sentVariables())->toBe(['id' => 'c8e96bce-fb65-4f1b-a97d-29df0e8cde94']);
});

it('lists folders with their subfolders by default', function () {
    answerValue(Operation::Folders, ['total' => 1, 'current_page' => 1, 'last_page' => 1, 'data' => [responseFixture('objects/folder')]]);

    $page = Autentique::folders()->list(type: FolderType::Organization, search: 'contracts', orderBy: 'name');

    expect($page->items[0])->toBeInstanceOf(Folder::class)
        ->and($page->hasMorePages)->toBeFalse()
        ->and(sentVariables())->toBe([
            'limit' => 20,
            'page' => 1,
            'display_children' => true,
            'type' => 'ORGANIZATION',
            'search' => 'contracts',
            'orderBy' => ['field' => 'name', 'direction' => 'DESC'],
        ]);
});

it('creates a folder, inside another when asked', function () {
    answerWith(Operation::CreateFolder, 'folder');

    Autentique::folders()->create('Signed contracts', parentId: 'parent-1', type: FolderType::Group);

    expect(sentVariables())->toBe(['folder' => ['name' => 'Signed contracts'], 'parent_id' => 'parent-1', 'type' => 'GROUP']);
});

it('renames a folder', function () {
    answerWith(Operation::UpdateFolder, 'folder');

    Autentique::folders()->rename('f1', 'Finished contracts');

    expect(sentVariables())->toBe(['id' => 'f1', 'folder' => ['name' => 'Finished contracts']]);
});

it('deletes a folder', function () {
    answerValue(Operation::DeleteFolder, true);

    expect(Autentique::folders()->delete('f1'))->toBeTrue()
        ->and(sentVariables())->toBe(['id' => 'f1']);
});

it('refuses a name shorter than Autentique accepts', function () {
    Http::fake();

    expect(fn() => Autentique::folders()->create('ab'))->toThrow(InvalidInput::class, 'at least 3 characters')
        ->and(fn() => Autentique::folders()->rename('f1', '  x  '))->toThrow(InvalidInput::class);

    Http::assertNothingSent();
});

it('reports the sixth subfolder Autentique refuses', function () {
    Http::fake(['*' => Http::response(['errors' => [[
        'message' => 'validation',
        'extensions' => ['validation' => ['parent_id' => ['max_subfolders:5']]],
    ]]])]);

    Autentique::folders()->create('Sixth', parentId: 'full');
})->throws(ValidationFailed::class, 'parent_id: max_subfolders:5');

it('shares a folder with people and groups', function () {
    answerWith(Operation::ShareFolder, 'folder');

    Autentique::folders()->share('f1', [
        Share::withEmail('user@example.com'),
        Share::withGroup('abc123', FolderRole::Editor),
    ], message: "Now I'm sharing this folder with you!");

    expect(sentVariables())->toBe([
        'folder_id' => 'f1',
        'shares' => [
            ['email' => 'user@example.com', 'role' => 'VIEWER'],
            ['group_id' => 'abc123', 'role' => 'EDITOR'],
        ],
        'email_message' => "Now I'm sharing this folder with you!",
    ]);
});

it('refuses to share with nobody', function () {
    Http::fake();

    Autentique::folders()->share('f1', []);
})->throws(InvalidInput::class);

it('changes sharing: a role, the link, its password', function (Closure $call, array $variables) {
    answerWith(Operation::UpdateSharing, 'folder');

    $call();

    expect(sentVariables())->toBe($variables);
})->with([
    'a role' => [fn() => Autentique::folders()->changeRole('f1', 'share-1', FolderRole::Editor), ['folder_id' => 'f1', 'sharing_id' => 'share-1', 'role' => 'EDITOR']],
    'by link' => [fn() => Autentique::folders()->shareByLink('f1'), ['folder_id' => 'f1', 'share_by_link' => true]],
    'by link with a password' => [fn() => Autentique::folders()->shareByLink('f1', 'secret'), ['folder_id' => 'f1', 'share_by_link' => true, 'password' => 'secret']],
    'no longer by link' => [fn() => Autentique::folders()->stopSharingByLink('f1'), ['folder_id' => 'f1', 'share_by_link' => false]],
    'without the password' => [fn() => Autentique::folders()->removeLinkPassword('f1'), ['folder_id' => 'f1', 'remove_password' => true]],
]);

it('lists the documents in a folder', function () {
    answerValue(Operation::DocumentsByFolder, ['has_more_pages' => false, 'data' => [responseFixture('objects/document')]]);

    $page = Autentique::folders()->documents('f1', status: DocumentStatus::Signed, onlySandbox: true);

    expect($page->items[0])->toBeInstanceOf(Document::class)
        ->and(sentOperation())->toBe('documentsByFolder')
        ->and(sentVariables())->toBe(['folder_id' => 'f1', 'limit' => 20, 'page' => 1, 'status' => 'SIGNED', 'onlySandbox' => true]);
});

it('includes sandbox documents in a folder when the configuration makes them the default', function () {
    config()->set('autentique.sandbox', true);
    answerValue(Operation::DocumentsByFolder, ['data' => []]);

    Autentique::folders()->documents('f1');

    expect(sentVariables()['showSandbox'] ?? null)->toBeTrue();
});

it('refuses a page that cannot exist', function () {
    Http::fake();

    Autentique::folders()->list(perPage: 0);
})->throws(InvalidInput::class);
