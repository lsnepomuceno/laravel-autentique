<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use LSNepomuceno\LaravelAutentique\Contracts\{FileSource, GraphQLClient};
use LSNepomuceno\LaravelAutentique\Data\{Document, Page};
use LSNepomuceno\LaravelAutentique\Data\Input\{DocumentChanges, NewDocument, Signer};
use LSNepomuceno\LaravelAutentique\Enums\{Context, DocumentStatus, DocumentType, OrderDirection};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Documents: creating them, and everything done to them afterwards.
 */
final readonly class Documents
{
    public function __construct(
        private GraphQLClient $client,
        private Repository $config,
    ) {}

    /**
     * Creates a document from a file and sends it to its signers.
     *
     * A fluent way to build the same call is `Autentique::newDocument()`.
     *
     * @param  list<Signer>  $signers
     * @param  ?bool  $sandbox  Null uses `autentique.sandbox`
     *                          (docs/decisions/0006-sandbox-is-chosen-per-call.md).
     * @param  ?string  $folderId  Files the document in a folder as it is created.
     * @param  ?int  $organizationId  Creates it in another of the account's organizations.
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function create(
        NewDocument $document,
        array $signers,
        FileSource $file,
        ?bool $sandbox = null,
        ?string $folderId = null,
        ?int $organizationId = null,
        DocumentType $type = DocumentType::Default,
    ): Document {
        if ($signers === []) {
            throw new InvalidInput('A document needs at least one signer.');
        }

        $markdown = Str::lower(pathinfo($file->name(), PATHINFO_EXTENSION)) === 'md';

        if ($type === DocumentType::WhatsappFlow && ! $markdown) {
            throw new InvalidInput('A WhatsApp Flow document is a Markdown file, and '
                . $file->name() . ' is not one.');
        }

        if ($type !== DocumentType::WhatsappFlow && $markdown) {
            throw new InvalidInput('A Markdown file is only accepted as a WhatsApp Flow document.');
        }

        $variables = array_filter([
            'document' => $document->toArray(),
            'signers' => array_map(fn(Signer $signer): array => $signer->toArray(), $signers),
            'sandbox' => $sandbox ?? $this->config->get('autentique.sandbox') === true,
            'folder_id' => $folderId,
            'organization_id' => $organizationId,
            'type' => $type === DocumentType::Default ? null : $type->value,
        ], fn(mixed $value): bool => $value !== null);

        return $this->document(Operation::CreateDocument, $variables, $file);
    }

    /**
     * One document, with its signers and files.
     *
     * Every document Autentique returns is billed, one at a time, whether it is
     * read here or in a listing.
     *
     * @throws AutentiqueException
     */
    public function find(string $id): Document
    {
        return $this->document(Operation::Document, ['id' => $id]);
    }

    /**
     * A page of the account's documents, newest first unless told otherwise.
     *
     * The page is small by default because every document returned is billed.
     * Sandbox documents are hidden by Autentique unless asked for; with
     * `$sandbox` null, `autentique.sandbox` decides whether they are included
     * (docs/decisions/0006-sandbox-is-chosen-per-call.md).
     *
     * @param  ?string  $search  Matches the document's name and its signers.
     * @param  ?bool  $sandbox  True includes sandbox documents, false leaves them out.
     * @param  bool  $onlySandbox  Lists sandbox documents and nothing else.
     * @return Page<Document>
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function list(
        int $page = 1,
        int $perPage = 20,
        ?DocumentStatus $status = null,
        ?string $search = null,
        ?string $name = null,
        ?string $signer = null,
        ?string $folderId = null,
        ?Context $context = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $until = null,
        ?bool $includeDeleted = null,
        ?bool $includeArchived = null,
        ?string $orderBy = null,
        OrderDirection $direction = OrderDirection::Descending,
        ?bool $sandbox = null,
        bool $onlySandbox = false,
    ): Page {
        if ($page < 1 || $perPage < 1) {
            throw new InvalidInput("Pages start at 1 and hold at least one document; page {$page} of {$perPage} given.");
        }

        $sandbox ??= $this->config->get('autentique.sandbox') === true;

        $variables = array_filter([
            'limit' => $perPage,
            'page' => $page,
            'status' => $status?->value,
            'search' => $search,
            'name' => $name,
            'signer' => $signer,
            'folder_id' => $folderId,
            'context' => $context?->value,
            'start_date' => $from?->format('Y-m-d'),
            'end_date' => $until?->format('Y-m-d'),
            'include_deleted' => $includeDeleted,
            'include_archived' => $includeArchived,
            'orderBy' => $orderBy === null ? null : ['field' => $orderBy, 'direction' => $direction->value],
            'showSandbox' => $onlySandbox ? null : ($sandbox ? true : null),
            'onlySandbox' => $onlySandbox ? true : null,
        ], fn(mixed $value): bool => $value !== null);

        $data = Payload::of($this->client->send(Operation::Documents, $variables));

        return Page::fromPayload(
            $data->object(Operation::Documents->field()) ?? Payload::of([]),
            Document::fromPayload(...),
        );
    }

    /**
     * Changes what can still be changed on a document, and returns it as it is
     * now.
     *
     * @throws AutentiqueException
     */
    public function update(string $id, DocumentChanges $changes): Document
    {
        return $this->document(Operation::UpdateDocument, ['id' => $id, 'document' => $changes->toArray()]);
    }

    /**
     * Stops anyone from signing from now on.
     *
     * Deleting does not: it moves the document to the trash, and whoever still
     * has the link can sign.
     *
     * @throws AutentiqueException
     */
    public function block(string $id): Document
    {
        return $this->update($id, DocumentChanges::block());
    }

    /**
     * Moves the document to the trash, as the dashboard does.
     *
     * **It does not stop signing**; `block()` does. With `$folderId`, every
     * signature of the document filed in that folder goes with it.
     *
     * @throws AutentiqueException
     */
    public function delete(string $id, ?string $folderId = null): bool
    {
        return $this->confirm(Operation::DeleteDocument, array_filter(
            ['id' => $id, 'folder_id' => $folderId],
            fn(mixed $value): bool => $value !== null,
        ));
    }

    /**
     * Signs the document as the token's owner, who must be one of its signers:
     * otherwise Autentique answers `signature_not_found`. It never signs on
     * anyone else's behalf.
     *
     * @throws AutentiqueException
     */
    public function sign(string $id, ?int $organizationId = null): bool
    {
        return $this->confirm(Operation::SignDocument, array_filter(
            ['id' => $id, 'organization_id' => $organizationId],
            fn(mixed $value): bool => $value !== null,
        ));
    }

    /**
     * Hands the document to another organization's group.
     *
     * @throws AutentiqueException
     */
    public function transfer(
        string $id,
        int $organizationId,
        int $groupId,
        ?int $currentGroupId = null,
        ?Context $context = null,
    ): bool {
        return $this->confirm(Operation::TransferDocument, array_filter([
            'id' => $id,
            'organization_id' => $organizationId,
            'group_id' => $groupId,
            'current_group_id' => $currentGroupId,
            'context' => $context?->value,
        ], fn(mixed $value): bool => $value !== null));
    }

    /**
     * Files the document in a folder, or out of any folder with null.
     *
     * A document already in a folder needs `$currentFolderId`. For an
     * organization's or a group's folder, pass the matching context.
     *
     * @throws AutentiqueException
     */
    public function moveToFolder(
        string $documentId,
        ?string $folderId,
        ?string $currentFolderId = null,
        ?Context $context = null,
    ): bool {
        return $this->confirm(Operation::MoveDocumentToFolder, array_filter([
            'document_id' => $documentId,
            'folder_id' => $folderId,
            'current_folder_id' => $currentFolderId,
            'context' => $context?->value,
        ], fn(mixed $value): bool => $value !== null) + ['folder_id' => null]);
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function confirm(Operation $operation, array $variables): bool
    {
        return Payload::of($this->client->send($operation, $variables))->bool($operation->field());
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function document(Operation $operation, array $variables, ?FileSource $file = null): Document
    {
        $data = Payload::of($this->client->send($operation, $variables, $file));

        return Document::fromPayload($data->object($operation->field()) ?? Payload::of([]));
    }
}
