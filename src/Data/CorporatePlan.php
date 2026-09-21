<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Enums\{CreditsRecurrence, IntervalType, Tier};
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A custom plan a Corporate account offers its child organizations.
 */
final readonly class CorporatePlan
{
    public function __construct(
        public string $id,
        public ?string $displayName,
        public ?int $documentAmount,
        public ?int $creditAmount,
        public ?CreditsRecurrence $creditsRecurrence,
        public ?int $interval,
        public ?IntervalType $intervalType,
        public ?Tier $tier,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->string('id'),
            displayName: $payload->nullableString('display_name'),
            documentAmount: $payload->nullableInt('document_amount'),
            creditAmount: $payload->nullableInt('credit_amount'),
            creditsRecurrence: $payload->enum('credits_recurrence_method', CreditsRecurrence::class),
            interval: $payload->nullableInt('interval'),
            intervalType: $payload->enum('interval_type', IntervalType::class),
            tier: $payload->enum('tier', Tier::class),
        );
    }
}
