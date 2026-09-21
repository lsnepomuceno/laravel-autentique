<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The owner of the token, as `me` answers.
 *
 * Its `id` is a 40 character hexadecimal string, not a UUID. `cpf` and `cnpj`
 * arrive formatted, `012.345.678-90`.
 */
final readonly class User
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $email,
        public ?string $phone,
        public ?string $cpf,
        public ?string $cnpj,
        public ?CarbonImmutable $birthday,
        public ?Subscription $subscription,
        public ?Organization $organization,
        /**
         * The user's group in their organization. Read here because Autentique
         * answers an organization's own `groups` with null.
         */
        public ?Group $group = null,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $subscription = $payload->object('subscription');
        $group = $payload->object('member.group');
        $organization = $payload->object('organization');

        return new self(
            id: $payload->string('id'),
            name: $payload->nullableString('name'),
            email: $payload->nullableString('email'),
            phone: $payload->nullableString('phone'),
            cpf: $payload->nullableString('cpf'),
            cnpj: $payload->nullableString('cnpj'),
            birthday: $payload->date('birthday'),
            subscription: $subscription === null ? null : Subscription::fromPayload($subscription),
            organization: $organization === null ? null : Organization::fromPayload($organization),
            group: $group === null || ! $group->has('id') ? null : Group::fromPayload($group),
        );
    }
}
