<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A signing link, exclusive to one signer, for you to deliver.
 */
final readonly class Link
{
    public function __construct(
        public ?string $id,
        public ?string $shortLink,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->nullableString('id'),
            shortLink: $payload->nullableString('short_link'),
        );
    }
}
