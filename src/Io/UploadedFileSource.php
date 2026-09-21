<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Io;

use Illuminate\Http\UploadedFile;
use LSNepomuceno\LaravelAutentique\Contracts\FileSource;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * A file received in the current request, sent without being stored first.
 */
final readonly class UploadedFileSource implements FileSource
{
    /**
     * @throws InvalidInput
     */
    public function __construct(private UploadedFile $file, private ?string $name = null)
    {
        if (! $file->isValid()) {
            throw new InvalidInput("The upload {$file->getClientOriginalName()} failed: {$file->getErrorMessage()}");
        }
    }

    #[\Override]
    public function name(): string
    {
        return $this->name ?? $this->file->getClientOriginalName();
    }

    /**
     * The upload's temporary file, as a stream, so a large document is not
     * held in memory.
     *
     * @return resource
     *
     * @throws InvalidInput
     */
    #[\Override]
    public function contents(): mixed
    {
        $path = $this->file->getRealPath();
        $stream = $path === false ? false : fopen($path, 'rb');

        if ($stream === false) {
            throw new InvalidInput("The upload {$this->name()} could not be read.");
        }

        return $stream;
    }
}
