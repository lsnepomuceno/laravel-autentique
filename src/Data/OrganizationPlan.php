<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The plan a child organization is on.
 */
final readonly class OrganizationPlan
{
    public function __construct(
        public ?int $organizationId,
        public ?string $planName,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            organizationId: $payload->nullableInt('organization_id'),
            planName: $payload->nullableString('subscription.name'),
        );
    }
}
