<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Enums\{FallbackBehavior, VerificationType};

/**
 * An additional identity check a signer goes through before signing. Each one
 * consumes verification credits.
 *
 * `UPLOAD`, `LIVE`, `PF_FACIAL` and `BIOMETRIC_AND_TEXT_EXTRACTION` fall back to
 * manual approval after their last attempt, unless `withoutFallback()` says
 * the document should be rejected instead.
 */
final readonly class SecurityVerification
{
    public function __construct(
        public VerificationType $type,
        public ?string $verifyPhone = null,
        public ?FallbackBehavior $fallback = null,
    ) {}

    /**
     * A code sent by SMS. Without a phone, the signer provides one.
     */
    public static function sms(?string $phone = null): self
    {
        return new self(VerificationType::Sms, $phone);
    }

    /**
     * A photo ID and a selfie, approved or rejected by you.
     */
    public static function manual(): self
    {
        return new self(VerificationType::Manual);
    }

    /**
     * The front and back of a photo ID.
     */
    public static function upload(): self
    {
        return new self(VerificationType::Upload);
    }

    /**
     * A photo ID, a selfie and a liveness video, matched automatically.
     */
    public static function live(): self
    {
        return new self(VerificationType::Live);
    }

    /**
     * A selfie checked by SERPRO against the Brazilian government's photo for
     * the signer's CPF.
     */
    public static function pfFacial(): self
    {
        return new self(VerificationType::PfFacial);
    }

    /**
     * Present in the API's schema beside `PF_FACIAL`; the documentation does
     * not describe it.
     */
    public static function pfFacialMatch(): self
    {
        return new self(VerificationType::PfFacialMatch);
    }

    public static function biometricAndTextExtraction(): self
    {
        return new self(VerificationType::BiometricAndTextExtraction);
    }

    public static function livenessAndTextExtraction(): self
    {
        return new self(VerificationType::LivenessAndTextExtraction);
    }

    /**
     * Reject the document when the automatic check runs out of attempts, rather
     * than falling back to manual approval.
     */
    public function withoutFallback(): self
    {
        return new self($this->type, $this->verifyPhone, FallbackBehavior::DisableFallback);
    }

    /**
     * Whether this is one of the photo checks a signer may have only one of.
     */
    public function isPhotoCheck(): bool
    {
        return in_array($this->type, [
            VerificationType::Manual,
            VerificationType::Upload,
            VerificationType::Live,
            VerificationType::PfFacial,
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'verify_phone' => $this->verifyPhone,
            'fallback_behavior' => $this->fallback?->value,
        ], fn(mixed $value): bool => $value !== null);
    }
}
