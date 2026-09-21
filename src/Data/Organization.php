<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * An organization the token's owner belongs to. Its `id` is an integer, which
 * is what `createDocument` and `transferDocument` take.
 */
final readonly class Organization
{
    /**
     * @param  list<Group>  $groups  Empty unless the operation selects them.
     */
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
        public ?string $cnpj,
        public array $groups = [],
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->int('id'),
            uuid: $payload->nullableString('uuid'),
            name: $payload->nullableString('name'),
            cnpj: $payload->nullableString('cnpj'),
            groups: array_map(Group::fromPayload(...), $payload->list('groups')),
        );
    }
}
