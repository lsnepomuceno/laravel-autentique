<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A member of a child organization.
 *
 * **`$apiToken` acts as this member.** Autentique returns it so a Corporate
 * account can work inside its child organizations
 * (`Autentique::withToken($member->apiToken)`); it belongs in the same places
 * as the account's own token, and nowhere else
 * (docs/decisions/0010-corporate-is-an-area-on-its-own-endpoint.md).
 */
final readonly class OrganizationMember
{
    public function __construct(
        public ?string $id,
        public ?string $userId,
        public ?string $name,
        public ?string $email,
        public ?string $cpf,
        public ?CarbonImmutable $birthday,
        public ?Group $group,
        #[\SensitiveParameter]
        public ?string $apiToken,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        $group = $payload->object('group');

        return new self(
            id: $payload->nullableString('id'),
            userId: $payload->nullableString('user.id'),
            name: $payload->nullableString('user.name'),
            email: $payload->nullableString('user.email'),
            cpf: $payload->nullableString('user.cpf'),
            birthday: $payload->date('user.birthday'),
            group: $group === null || ! $group->has('id') ? null : Group::fromPayload($group),
            apiToken: $payload->nullableString('api_token.access_token'),
        );
    }
}
