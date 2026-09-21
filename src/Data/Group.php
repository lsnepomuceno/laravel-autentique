<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A group inside an organization. New organizations get two by default,
 * `Administrador` and `Sem grupo`.
 */
final readonly class Group
{
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
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
        );
    }
}
