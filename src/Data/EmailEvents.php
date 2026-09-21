<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * What happened to the email asking a signer to sign.
 *
 * `opened` and `delivered` depend on the signer's email client, and may never
 * be recorded. `refused` and `reason` say the email bounced, and why.
 */
final readonly class EmailEvents
{
    public function __construct(
        public ?CarbonImmutable $sent,
        public ?CarbonImmutable $opened,
        public ?CarbonImmutable $delivered,
        public ?CarbonImmutable $refused,
        public ?string $reason,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            sent: $payload->date('sent'),
            opened: $payload->date('opened'),
            delivered: $payload->date('delivered'),
            refused: $payload->date('refused'),
            reason: $payload->nullableString('reason'),
        );
    }
}
