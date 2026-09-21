<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

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
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            hasPremiumFeatures: $payload->bool('has_premium_features'),
            documents: $payload->nullableInt('documents'),
            credits: $payload->nullableInt('credits'),
        );
    }
}
