<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Where a signer was, as Autentique located them from their connection.
 */
final readonly class Geolocation
{
    public function __construct(
        public ?string $country,
        public ?string $countryIso,
        public ?string $state,
        public ?string $stateIso,
        public ?string $city,
        public ?string $zipcode,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            country: $payload->nullableString('country'),
            countryIso: $payload->nullableString('countryISO'),
            state: $payload->nullableString('state'),
            stateIso: $payload->nullableString('stateISO'),
            city: $payload->nullableString('city'),
            zipcode: $payload->nullableString('zipcode'),
            latitude: $payload->nullableFloat('latitude'),
            longitude: $payload->nullableFloat('longitude'),
        );
    }
}
