<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Enums\{FallbackBehavior, VerificationType};
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * An identity check a signer goes through, and where it stands.
 *
 * `$id` is what `approveBiometric` and `rejectBiometric` take. `$images` are
 * the photos a signer sent for a manual check, keyed as Autentique keys them
 * (`front`, `selfie`).
 */
final readonly class Verification
{
    /**
     * @param  array<string, string>  $images
     */
    public function __construct(
        public ?int $id,
        public ?VerificationType $type,
        public ?string $verifyPhone,
        public ?FallbackBehavior $fallback,
        public ?string $payloadUrl,
        public ?string $payloadReference,
        public array $images,
        public ?int $confidence,
        public ?CarbonImmutable $verifiedAt,
        public ?int $maxAttempts,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        $images = [];

        foreach ($payload->object('user.images')?->all() ?? [] as $key => $url) {
            if (is_string($url)) {
                $images[(string) $key] = $url;
            }
        }

        return new self(
            id: $payload->nullableInt('id'),
            type: $payload->enum('type', VerificationType::class),
            verifyPhone: $payload->nullableString('verify_phone'),
            fallback: $payload->enum('fallback_behavior', FallbackBehavior::class),
            payloadUrl: $payload->nullableString('payload.url'),
            payloadReference: $payload->nullableString('payload.reference'),
            images: $images,
            confidence: $payload->nullableInt('user.confidence'),
            verifiedAt: $payload->date('verified_at'),
            maxAttempts: $payload->nullableInt('max_attempt'),
        );
    }
}
