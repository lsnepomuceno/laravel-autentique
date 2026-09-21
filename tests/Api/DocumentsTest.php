<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Data\{Document, Page};
use LSNepomuceno\LaravelAutentique\Data\Input\DocumentChanges;
use LSNepomuceno\LaravelAutentique\Enums\{Context, DocumentStatus, OrderDirection, Reminder};
use LSNepomuceno\LaravelAutentique\Exceptions\{InvalidInput, NotFound};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(fn() => config()->set('autentique.token', 'the-token'));

it('finds a document by its id', function () {
    answerWith(Operation::Document, 'document');

    $document = Autentique::documents()->find('89c7d2ab31f9f5a13b3d20ecf53319af387e54d240ae7be993');

    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->signatures)->toHaveCount(2)
        ->and(sentOperation())->toBe('document')
        ->and(sentVariables())->toBe(['id' => '89c7d2ab31f9f5a13b3d20ecf53319af387e54d240ae7be993']);
});

it('says a document that does not exist is not found', function () {
    Http::fake(['*' => Http::response(['errors' => [['message' => 'document_not_found', 'path' => ['document']]], 'data' => ['document' => null]])]);

    Autentique::documents()->find('nope');
})->throws(NotFound::class);

it('lists a small page by default, hiding sandbox documents', function () {
    answerValue(Operation::Documents, [
        'total' => 41,
        'per_page' => 20,
        'current_page' => 1,
        'last_page' => 3,
        'has_more_pages' => true,
        'data' => [responseFixture('objects/document'), responseFixture('objects/document')],
    ]);

    $page = Autentique::documents()->list();

    expect($page)->toBeInstanceOf(Page::class)
        ->and($page)->toHaveCount(2)
        ->and($page->items[0])->toBeInstanceOf(Document::class)
        ->and($page->total)->toBe(41)
        ->and($page->lastPage)->toBe(3)
        ->and($page->hasMorePages)->toBeTrue()
        ->and(iterator_to_array($page))->toHaveCount(2)
        ->and(sentVariables())->toBe(['limit' => 20, 'page' => 1]);
});

it('sends every filter it is given', function () {
    answerValue(Operation::Documents, ['data' => []]);

    Autentique::documents()->list(
        page: 2,
        perPage: 60,
        status: DocumentStatus::Pending,
        search: 'contract',
        name: 'Marketing',
        signer: 'ana@example.com',
        folderId: 'folder-1',
        context: Context::Organization,
        from: CarbonImmutable::parse('2026-01-01'),
        until: CarbonImmutable::parse('2026-01-31'),
        includeDeleted: true,
        includeArchived: false,
        orderBy: 'created_at',
        direction: OrderDirection::Ascending,
    );

    expect(sentVariables())->toBe([
        'limit' => 60,
        'page' => 2,
        'status' => 'PENDING',
        'search' => 'contract',
        'name' => 'Marketing',
        'signer' => 'ana@example.com',
        'folder_id' => 'folder-1',
        'context' => 'ORGANIZATION',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
        'include_deleted' => true,
        'include_archived' => false,
        'orderBy' => ['field' => 'created_at', 'direction' => 'ASC'],
    ]);
});

it('includes sandbox documents when the configuration makes them the default', function () {
    config()->set('autentique.sandbox', true);
    answerValue(Operation::Documents, ['data' => []]);

    Autentique::documents()->list();

    expect(sentVariables()['showSandbox'] ?? null)->toBeTrue();
});

it('lists only sandbox documents when asked', function () {
    answerValue(Operation::Documents, ['data' => []]);

    Autentique::documents()->list(onlySandbox: true);

    expect(sentVariables())->toBe(['limit' => 20, 'page' => 1, 'onlySandbox' => true]);
});

it('works out whether there are more pages when Autentique does not say', function () {
    answerValue(Operation::Documents, ['current_page' => 2, 'last_page' => 2, 'data' => []]);

    expect(Autentique::documents()->list(page: 2)->hasMorePages)->toBeFalse();
});

it('refuses a page that cannot exist', function () {
    Http::fake();

    expect(fn() => Autentique::documents()->list(page: 0))->toThrow(InvalidInput::class);

    Http::assertNothingSent();
});

it('updates only what changes', function () {
    answerWith(Operation::UpdateDocument, 'document');

    Autentique::documents()->update('doc-1', new DocumentChanges(
        name: 'Renamed',
        reminder: Reminder::Daily,
        watchers: ['legal@example.com'],
        cc: ['cc@example.com'],
    ));

    expect(sentOperation())->toBe('updateDocument')
        ->and(sentVariables())->toBe([
            'id' => 'doc-1',
            'document' => [
                'name' => 'Renamed',
                'reminder' => 'DAILY',
                'watchers' => ['legal@example.com'],
                'cc' => [['email' => 'cc@example.com']],
            ],
        ]);
});

it('blocks signing by moving the deadline to now', function () {
    CarbonImmutable::setTestNow('2026-09-21 12:00:00.500');
    answerWith(Operation::UpdateDocument, 'document');

    Autentique::documents()->block('doc-1');

    expect(sentVariables())->toBe(['id' => 'doc-1', 'document' => ['deadline_at' => '2026-09-21T12:00:00.500Z']]);

    CarbonImmutable::setTestNow();
});

it('refuses an update that changes nothing, or breaks a rule', function (Closure $build, string $message) {
    expect($build)->toThrow(InvalidInput::class, $message);
})->with([
    'nothing' => [fn() => new DocumentChanges(), 'at least one change'],
    'an empty name' => [fn() => new DocumentChanges(name: ' '), 'renamed to nothing'],
    'a reminder with refusable off' => [fn() => new DocumentChanges(reminder: Reminder::Weekly, refusable: false), 'makes the document refusable'],
    'no audit page with the old style' => [fn() => new DocumentChanges(newSignatureStyle: false, showAuditPage: false), 'new signature style'],
]);

it('deletes, signs and transfers, answering whether it worked', function (Closure $call, Operation $operation, array $variables) {
    answerValue($operation, true);

    expect($call())->toBeTrue()
        ->and(sentOperation())->toBe($operation->operationName())
        ->and(sentVariables())->toBe($variables);
})->with([
    'delete' => [fn() => Autentique::documents()->delete('doc-1'), Operation::DeleteDocument, ['id' => 'doc-1']],
    'delete from a folder' => [fn() => Autentique::documents()->delete('doc-1', 'folder-a'), Operation::DeleteDocument, ['id' => 'doc-1', 'folder_id' => 'folder-a']],
    'sign' => [fn() => Autentique::documents()->sign('doc-1'), Operation::SignDocument, ['id' => 'doc-1']],
    'sign for an organization' => [fn() => Autentique::documents()->sign('doc-1', 179), Operation::SignDocument, ['id' => 'doc-1', 'organization_id' => 179]],
    'transfer' => [
        fn() => Autentique::documents()->transfer('doc-1', organizationId: 179, groupId: 7, currentGroupId: 3, context: Context::Group),
        Operation::TransferDocument,
        ['id' => 'doc-1', 'organization_id' => 179, 'group_id' => 7, 'current_group_id' => 3, 'context' => 'GROUP'],
    ],
]);

it('moves a document into a folder, and out of every folder', function () {
    answerValue(Operation::MoveDocumentToFolder, true);

    Autentique::documents()->moveToFolder('doc-1', 'folder-b', currentFolderId: 'folder-a', context: Context::Organization);

    expect(sentVariables())->toBe([
        'document_id' => 'doc-1',
        'folder_id' => 'folder-b',
        'current_folder_id' => 'folder-a',
        'context' => 'ORGANIZATION',
    ]);

    Autentique::documents()->moveToFolder('doc-1', null);

    expect(sentVariables())->toBe(['document_id' => 'doc-1', 'folder_id' => null]);
});

it('answers false when Autentique does', function () {
    answerValue(Operation::DeleteDocument, false);

    expect(Autentique::documents()->delete('doc-1'))->toBeFalse();
});

it('never sends a request the fake did not see', function () {
    answerValue(Operation::DeleteDocument, true);

    Autentique::documents()->delete('doc-1');

    Http::assertSent(fn(Request $request): bool => $request->hasHeader('Authorization', 'Bearer the-token'));
});
