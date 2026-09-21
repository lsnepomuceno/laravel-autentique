<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Io;

use Illuminate\Contracts\Filesystem\Filesystem;
use LSNepomuceno\LaravelAutentique\Contracts\FileSource;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * A file on one of the application's `Storage` disks, streamed from it.
 *
 * A document on S3 reaches Autentique without ever being written to a local
 * file.
 */
final readonly class DiskFile implements FileSource
{
    /**
     * @throws InvalidInput
     */
    public function __construct(private Filesystem $disk, private string $path, private ?string $name = null)
    {
        if (! $disk->exists($path)) {
            throw new InvalidInput("There is no file at {$path} on the disk.");
        }
    }

    #[\Override]
    public function name(): string
    {
        return $this->name ?? basename($this->path);
    }

    /**
     * @return resource
     *
     * @throws InvalidInput
     */
    #[\Override]
    public function contents(): mixed
    {
        return $this->disk->readStream($this->path) ?? throw new InvalidInput("The file at {$this->path} could not be read from the disk.");
    }
}
