<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Io;

use LSNepomuceno\LaravelAutentique\Contracts\FileSource;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * A file on the local filesystem, read as a stream when it is sent.
 */
final readonly class PathFile implements FileSource
{
    /**
     * @param  ?string  $name  The name Autentique receives, the file's own when absent.
     *
     * @throws InvalidInput
     */
    public function __construct(private string $path, private ?string $name = null)
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidInput("There is no readable file at {$path}.");
        }
    }

    #[\Override]
    public function name(): string
    {
        return $this->name ?? basename($this->path);
    }

    /**
     * A stream rather than the bytes, so a large document is not held in
     * memory. `File::get()` reads the whole file, and the framework offers no
     * stream for a local path outside a disk.
     *
     * @return resource
     *
     * @throws InvalidInput
     */
    #[\Override]
    public function contents(): mixed
    {
        $stream = fopen($this->path, 'rb');

        if ($stream === false) {
            throw new InvalidInput("The file at {$this->path} could not be opened.");
        }

        return $stream;
    }
}
