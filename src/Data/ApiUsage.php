<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * What a child organization's use of the API cost over a period: the prices in
 * force, and what was used each day.
 */
final readonly class ApiUsage
{
    /**
     * @param  list<array{validSince: ?CarbonImmutable, currency: ?string, prices: ApiUsageItems}>  $pricing
     * @param  list<array{day: ?CarbonImmutable, used: ApiUsageItems}>  $days
     */
    public function __construct(
        public array $pricing,
        public array $days,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            pricing: array_map(fn(Payload $price): array => [
                'validSince' => $price->date('valid_since'),
                'currency' => $price->nullableString('currency'),
                'prices' => ApiUsageItems::fromPayload($price->object('items') ?? Payload::of([])),
            ], $payload->list('pricing')),
            days: array_map(fn(Payload $day): array => [
                'day' => $day->date('day'),
                'used' => ApiUsageItems::fromPayload($day->object('items') ?? Payload::of([])),
            ], $payload->list('usage')),
        );
    }
}
