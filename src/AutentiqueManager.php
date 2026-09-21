<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as Filesystems;
use Illuminate\Http\UploadedFile;
use LSNepomuceno\LaravelAutentique\Api\{Account, Documents, PendingDocument, Signers};
use LSNepomuceno\LaravelAutentique\Contracts\{Autentique, FileSource, GraphQLClient};
use LSNepomuceno\LaravelAutentique\Io\{DiskFile, PathFile, UploadedFileSource};

/**
 * The default implementation of the contract, bound as a singleton.
 */
final readonly class AutentiqueManager implements Autentique
{
    public function __construct(
        private GraphQLClient $client,
        private Repository $config,
        private Filesystems $disks,
    ) {}

    #[\Override]
    public function account(): Account
    {
        return new Account($this->client);
    }

    #[\Override]
    public function documents(): Documents
    {
        return new Documents($this->client, $this->config);
    }

    #[\Override]
    public function signers(): Signers
    {
        return new Signers($this->client);
    }

    #[\Override]
    public function newDocument(string $name): PendingDocument
    {
        return new PendingDocument($this->documents(), $name);
    }

    #[\Override]
    public function fromPath(string $path, ?string $name = null): FileSource
    {
        return new PathFile($path, $name);
    }

    #[\Override]
    public function fromUpload(UploadedFile $file, ?string $name = null): FileSource
    {
        return new UploadedFileSource($file, $name);
    }

    #[\Override]
    public function fromDisk(string $disk, string $path, ?string $name = null): FileSource
    {
        return new DiskFile($this->disks->disk($disk), $path, $name);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    #[\Override]
    public function query(string $graphql, array $variables = []): array
    {
        return $this->client->raw($graphql, $variables);
    }
}
