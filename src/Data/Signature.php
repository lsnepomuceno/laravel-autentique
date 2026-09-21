<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Enums\{Action, DeliveryMethod, SignerType};
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * One signer on a document, and everything that happened to their request.
 *
 * `$publicId` identifies the signer in every later call: resending, removing,
 * creating a link, approving a verification.
 */
final readonly class Signature
{
    /**
     * @param  list<SignaturePosition>  $positions
     * @param  list<Verification>  $verifications
     */
    public function __construct(
        public string $publicId,
        public ?string $name,
        public ?string $email,
        public ?DeliveryMethod $deliveryMethod,
        public ?SignerType $type,
        public ?Action $action,
        public ?CarbonImmutable $createdAt,
        /** The link to deliver, for a signer reached by link. */
        public ?Link $link,
        /** The account behind the signer, once there is one. */
        public ?User $user,
        public ?EmailEvents $emailEvents,
        public array $positions,
        public array $verifications,
        public ?Event $viewed,
        public ?Event $signed,
        public ?Event $rejected,
        /** Signed, and waiting for a biometric check to be approved. */
        public ?Event $signedUnapproved,
        public ?Event $biometricApproved,
        public ?Event $biometricRejected,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $event = function (string $key) use ($payload): ?Event {
            $value = $payload->object($key);

            return $value === null ? null : Event::fromPayload($value);
        };

        $link = $payload->object('link');
        $user = $payload->object('user');
        $emailEvents = $payload->object('email_events');

        return new self(
            publicId: $payload->string('public_id'),
            name: $payload->nullableString('name'),
            email: $payload->nullableString('email'),
            deliveryMethod: $payload->enum('delivery_method', DeliveryMethod::class),
            type: $payload->enum('type', SignerType::class),
            action: $payload->enum('action.name', Action::class),
            createdAt: $payload->date('created_at'),
            link: $link === null ? null : Link::fromPayload($link),
            user: $user === null || ! $user->has('id') ? null : User::fromPayload($user),
            emailEvents: $emailEvents === null ? null : EmailEvents::fromPayload($emailEvents),
            positions: array_map(SignaturePosition::fromPayload(...), $payload->list('positions')),
            verifications: array_map(Verification::fromPayload(...), $payload->list('verifications')),
            viewed: $event('viewed'),
            signed: $event('signed'),
            rejected: $event('rejected'),
            signedUnapproved: $event('signed_unapproved'),
            biometricApproved: $event('biometric_approved'),
            biometricRejected: $event('biometric_rejected'),
        );
    }

    public function hasSigned(): bool
    {
        return $this->signed !== null;
    }

    public function hasRejected(): bool
    {
        return $this->rejected !== null;
    }
}
