<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Enums\PositionElement;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A position on a document where something is stamped for a signer, as
 * Autentique holds it. `x` and `y` are percentages of the page.
 */
final readonly class SignaturePosition
{
    public function __construct(
        public ?PositionElement $element,
        public ?float $x,
        public ?float $y,
        public ?int $page,
        public ?float $angle,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            element: $payload->enum('element', PositionElement::class),
            x: $payload->nullableFloat('x'),
            y: $payload->nullableFloat('y'),
            page: $payload->nullableInt('z'),
            angle: $payload->nullableFloat('angle'),
        );
    }
}
