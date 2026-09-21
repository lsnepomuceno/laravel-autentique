<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Enums\SignatureAppearance;

/**
 * Notifications and signing options, sent as the document's `configs`.
 *
 * `pdfa` keeps an attached PDF/A as PDF/A; it does not convert a document.
 * Autentique forces `lockUserData` and the new signature style when it is on.
 */
final readonly class DocumentConfig
{
    public function __construct(
        /** Email every signer when the last one signs. */
        public ?bool $notifyFinished = null,
        /** Email each signer after they sign. */
        public ?bool $notifySigned = null,
        /** The only signature appearance signers may use. */
        public ?SignatureAppearance $signatureAppearance = null,
        /** Keep the PDF's metadata on a qualified signature. */
        public ?bool $keepMetadata = null,
        /** Show signers' data as it was when they signed. */
        public ?bool $lockUserData = null,
        /** Watchers receive the signing notifications too. */
        public ?bool $notifyWatchers = null,
        public ?bool $pdfa = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'notification_finished' => $this->notifyFinished,
            'notification_signed' => $this->notifySigned,
            'signature_appearance' => $this->signatureAppearance?->value,
            'keep_metadata' => $this->keepMetadata,
            'lock_user_data' => $this->lockUserData,
            'notify_watchers' => $this->notifyWatchers,
            'pdfa' => $this->pdfa,
        ], fn(mixed $value): bool => $value !== null);
    }
}
