<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Contracts;

use Illuminate\Http\UploadedFile;
use LSNepomuceno\LaravelAutentique\Api\{Account, Corporate, Documents, Folders, OAuth, Organizations, PendingDocument, Signers};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};

/**
 * What this package exposes, resolved from the container or reached through
 * the `Autentique` facade.
 */
interface Autentique
{
    /**
     * The account the token belongs to: `account()->me()`.
     */
    public function account(): Account;

    /**
     * Documents: creating, reading, changing and removing them.
     */
    public function documents(): Documents;

    /**
     * The signers of an existing document: adding, removing, resending, links
     * and biometric approval.
     */
    public function signers(): Signers;

    /**
     * Folders, sharing them, and the documents inside them.
     */
    public function folders(): Folders;

    /**
     * The organizations the token's owner belongs to, and the email templates.
     */
    public function organizations(): Organizations;

    /**
     * The Corporate plan's extension of the API, on its own endpoint.
     */
    public function corporate(): Corporate;

    /**
     * OAuth 2.0 with PKCE, for acting on behalf of accounts that authorize it.
     */
    public function oauth(): OAuth;

    /**
     * The whole API, sending another token: an OAuth access token, or a Corporate
     * member's. The configured token is untouched.
     */
    public function withToken(#[\SensitiveParameter] string $token): self;

    /**
     * Starts a document, sent with `->send()`.
     */
    public function newDocument(string $name): PendingDocument;

    /**
     * A file on the local filesystem, to upload.
     *
     * @throws InvalidInput
     */
    public function fromPath(string $path, ?string $name = null): FileSource;

    /**
     * A file received in the current request, to upload.
     *
     * @throws InvalidInput
     */
    public function fromUpload(UploadedFile $file, ?string $name = null): FileSource;

    /**
     * A file on one of the application's `Storage` disks, streamed from it.
     *
     * @throws InvalidInput
     */
    public function fromDisk(string $disk, string $path, ?string $name = null): FileSource;

    /**
     * Sends a GraphQL document the package does not ship, and returns its
     * `data`.
     *
     * The escape hatch, for a field or an operation this package does not
     * model. Values still travel as variables; the document is the caller's.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    public function query(string $graphql, array $variables = []): array;
}
