<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Contracts;

/**
 * A file to upload with a document: its name and its bytes.
 *
 * The contents may be a stream, so a document on a remote disk is sent without
 * being copied to a local file first.
 */
interface FileSource
{
    /**
     * The file name Autentique receives, extension included: the extension is
     * how it tells a PDF from HTML or Markdown.
     */
    public function name(): string;

    /**
     * @return string|resource
     */
    public function contents(): mixed;
}
