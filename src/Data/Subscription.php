<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The plan behind an account: documents left, and verification credits left.
 */
final readonly class Subscription
{
    public function __construct(
        public bool $hasPremiumFeatures,
        public ?int $documents,
        public ?int $credits,
        /** The plan's name, on the Corporate endpoint. */
        public ?string $name = null,
        /** Verification credits kept apart from the plan's, with a `DETACHED` custom plan. */
        public ?int $creditsBonus = null,
        public ?CarbonImmutable $renewsAt = null,
        public ?CarbonImmutable $boughtAt = null,
        public ?CarbonImmutable $expiresAt = null,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            hasPremiumFeatures: $payload->bool('has_premium_features'),
            documents: $payload->nullableInt('documents'),
            credits: $payload->nullableInt('credits'),
            name: $payload->nullableString('name'),
            creditsBonus: $payload->nullableInt('credits_bonus'),
            renewsAt: $payload->date('renew_at'),
            boughtAt: $payload->date('bought_at'),
            expiresAt: $payload->date('expires_at'),
        );
    }
}
