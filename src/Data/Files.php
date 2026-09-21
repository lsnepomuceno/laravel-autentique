<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Where a document's files can be downloaded.
 *
 * `original` is the file as uploaded. `signed` carries the signatures, and
 * exists once there is one. `certified` and `pades` are the certificate and
 * the PAdES version, Autentique's own.
 */
final readonly class Files
{
    public function __construct(
        public ?string $original,
        public ?string $signed,
        public ?string $certified,
        public ?string $pades,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            original: $payload->nullableString('original'),
            signed: $payload->nullableString('signed'),
            certified: $payload->nullableString('certified'),
            pades: $payload->nullableString('pades'),
        );
    }
}
