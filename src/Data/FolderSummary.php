<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A folder as another folder mentions it: its parent, or one of its subfolders.
 */
final readonly class FolderSummary
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $path,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->string('id'),
            name: $payload->nullableString('name'),
            path: $payload->nullableString('path'),
        );
    }
}
