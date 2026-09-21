<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Data\{Document, Folder, Page};
use LSNepomuceno\LaravelAutentique\Data\Input\Share;
use LSNepomuceno\LaravelAutentique\Enums\{DocumentStatus, FolderRole, FolderType, OrderDirection};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Folders, who they are shared with, and the documents inside them.
 *
 * A folder's name has at least three characters, and a folder holds at most
 * five subfolders; the first is refused here, the second by Autentique.
 */
final readonly class Folders
{
    public function __construct(
        private GraphQLClient $client,
        private Repository $config,
    ) {}

    /**
     * A page of folders, subfolders included unless `$withChildren` is false.
     *
     * @return Page<Folder>
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function list(
        int $page = 1,
        int $perPage = 20,
        bool $withChildren = true,
        ?FolderType $type = null,
        ?string $search = null,
        ?string $orderBy = null,
        OrderDirection $direction = OrderDirection::Descending,
    ): Page {
        $this->guardPage($page, $perPage);

        $data = Payload::of($this->client->send(Operation::Folders, array_filter([
            'limit' => $perPage,
            'page' => $page,
            'display_children' => $withChildren,
            'type' => $type?->value,
            'search' => $search,
            'orderBy' => $orderBy === null ? null : ['field' => $orderBy, 'direction' => $direction->value],
        ], fn(mixed $value): bool => $value !== null)));

        return Page::fromPayload($data->object(Operation::Folders->field()) ?? Payload::of([]), Folder::fromPayload(...));
    }

    /**
     * One folder, with its parent, its subfolders and who it is shared with.
     *
     * @throws AutentiqueException
     */
    public function find(string $id): Folder
    {
        return $this->folder(Operation::Folder, ['id' => $id]);
    }

    /**
     * Creates a folder, inside `$parentId` when given.
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function create(string $name, ?string $parentId = null): Folder
    {
        $this->guardName($name);

        return $this->folder(Operation::CreateFolder, array_filter([
            'folder' => ['name' => $name],
            'parent_id' => $parentId,
        ], fn(mixed $value): bool => $value !== null));
    }

    /**
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function rename(string $id, string $name): Folder
    {
        $this->guardName($name);

        return $this->folder(Operation::UpdateFolder, ['id' => $id, 'folder' => ['name' => $name]]);
    }

    /**
     * Deletes a folder. The documents in it stay, in no folder.
     *
     * @throws AutentiqueException
     */
    public function delete(string $id): bool
    {
        return Payload::of($this->client->send(Operation::DeleteFolder, ['id' => $id]))->bool(Operation::DeleteFolder->field());
    }

    /**
     * Shares a folder with people and groups, each with a role.
     *
     * @param  list<Share>  $shares
     * @param  ?string  $message  Sent in the email that tells them.
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function share(string $folderId, array $shares, ?string $message = null): Folder
    {
        if ($shares === []) {
            throw new InvalidInput('Sharing a folder needs at least one person or group.');
        }

        return $this->folder(Operation::ShareFolder, array_filter([
            'folder_id' => $folderId,
            'shares' => array_map(fn(Share $share): array => $share->toArray(), $shares),
            'email_message' => $message,
        ], fn(mixed $value): bool => $value !== null));
    }

    /**
     * Changes what someone the folder is shared with may do.
     *
     * @param  string  $sharingId  `Data\FolderShare::$id`.
     *
     * @throws AutentiqueException
     */
    public function changeRole(string $folderId, string $sharingId, FolderRole $role): Folder
    {
        return $this->sharing(['sharing_id' => $sharingId, 'role' => $role->value], $folderId);
    }

    /**
     * Lets anyone holding the folder's link see it, behind a password when
     * one is given.
     *
     * @throws AutentiqueException
     */
    public function shareByLink(string $folderId, #[\SensitiveParameter] ?string $password = null): Folder
    {
        return $this->sharing(array_filter(
            ['share_by_link' => true, 'password' => $password],
            fn(mixed $value): bool => $value !== null,
        ), $folderId);
    }

    /**
     * Stops sharing the folder by link.
     *
     * @throws AutentiqueException
     */
    public function stopSharingByLink(string $folderId): Folder
    {
        return $this->sharing(['share_by_link' => false], $folderId);
    }

    /**
     * Keeps sharing by link, without the password.
     *
     * @throws AutentiqueException
     */
    public function removeLinkPassword(string $folderId): Folder
    {
        return $this->sharing(['remove_password' => true], $folderId);
    }

    /**
     * A page of the documents in a folder, with the same billing and sandbox
     * rules as `Api\Documents::list()`.
     *
     * @return Page<Document>
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function documents(
        string $folderId,
        int $page = 1,
        int $perPage = 20,
        ?DocumentStatus $status = null,
        ?string $search = null,
        ?string $orderBy = null,
        OrderDirection $direction = OrderDirection::Descending,
        ?bool $sandbox = null,
        bool $onlySandbox = false,
    ): Page {
        $this->guardPage($page, $perPage);

        $sandbox ??= $this->config->get('autentique.sandbox') === true;

        $data = Payload::of($this->client->send(Operation::DocumentsByFolder, array_filter([
            'folder_id' => $folderId,
            'limit' => $perPage,
            'page' => $page,
            'status' => $status?->value,
            'search' => $search,
            'orderBy' => $orderBy === null ? null : ['field' => $orderBy, 'direction' => $direction->value],
            // `onlySandbox` alone is ignored by the API, and beside
            // `showSandbox` it filters nothing (measured on 2026-09-21), so the
            // sandbox documents are asked for and the page is filtered here.
            'showSandbox' => $onlySandbox || $sandbox ? true : null,
            'onlySandbox' => $onlySandbox ? true : null,
        ], fn(mixed $value): bool => $value !== null)));

        $page = Page::fromPayload(
            $data->object(Operation::DocumentsByFolder->field()) ?? Payload::of([]),
            Document::fromPayload(...),
        );

        return $onlySandbox ? $page->filter(fn(Document $document): bool => $document->sandbox === true) : $page;
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function sharing(array $variables, string $folderId): Folder
    {
        return $this->folder(Operation::UpdateSharing, ['folder_id' => $folderId, ...$variables]);
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function folder(Operation $operation, array $variables): Folder
    {
        $data = Payload::of($this->client->send($operation, $variables));

        return Folder::fromPayload($data->object($operation->field()) ?? Payload::of([]));
    }

    /**
     * @throws InvalidInput
     */
    private function guardName(string $name): void
    {
        if (Str::length(trim($name)) < 3) {
            throw new InvalidInput("A folder's name has at least 3 characters, \"{$name}\" given.");
        }
    }

    /**
     * @throws InvalidInput
     */
    private function guardPage(int $page, int $perPage): void
    {
        if ($page < 1 || $perPage < 1) {
            throw new InvalidInput("Pages start at 1 and hold at least one item; page {$page} of {$perPage} given.");
        }
    }
}
