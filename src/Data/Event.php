<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Something a signer did: viewed, signed, rejected, or went through a
 * biometric approval. When it happened, and from where.
 */
final readonly class Event
{
    public function __construct(
        public ?CarbonImmutable $at,
        public ?string $ip,
        public ?int $port,
        /** The reason a signer gave when rejecting. */
        public ?string $reason,
        public ?Geolocation $geolocation,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        $geolocation = $payload->object('geolocation');

        return new self(
            at: $payload->date('created_at'),
            ip: $payload->nullableString('ip'),
            port: $payload->nullableInt('port'),
            reason: $payload->nullableString('reason'),
            geolocation: $geolocation === null ? null : Geolocation::fromPayload($geolocation),
        );
    }
}
