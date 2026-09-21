<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Enums\{Footer, Reminder};
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A document, its settings, its files and its signers, as the operations that
 * return one select it (`fragments/DocumentFields.graphql`).
 *
 * `$id` is a 50 character hexadecimal string, not a UUID, and is what every
 * later call about the document takes.
 */
final readonly class Document
{
    /**
     * @param  list<Signature>  $signatures
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $message,
        public ?Reminder $reminder,
        public ?bool $refusable,
        public ?bool $sortable,
        public ?bool $qualified,
        public ?bool $sandbox,
        public ?bool $stopOnRejected,
        public ?bool $newSignatureStyle,
        public ?bool $showAuditPage,
        public ?Footer $footer,
        public ?int $emailTemplateId,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $deletedAt,
        public ?CarbonImmutable $deadlineAt,
        public ?CarbonImmutable $expirationAt,
        public ?Files $files,
        public array $signatures,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $files = $payload->object('files');

        return new self(
            id: $payload->string('id'),
            name: $payload->string('name'),
            message: $payload->nullableString('message'),
            reminder: $payload->enum('reminder', Reminder::class),
            refusable: $payload->nullableBool('refusable'),
            sortable: $payload->nullableBool('sortable'),
            qualified: $payload->nullableBool('qualified'),
            sandbox: $payload->nullableBool('sandbox'),
            stopOnRejected: $payload->nullableBool('stop_on_rejected'),
            newSignatureStyle: $payload->nullableBool('new_signature_style'),
            showAuditPage: $payload->nullableBool('show_audit_page'),
            footer: $payload->enum('footer', Footer::class),
            emailTemplateId: $payload->nullableInt('email_template_id'),
            createdAt: $payload->date('created_at'),
            deletedAt: $payload->date('deleted_at'),
            deadlineAt: $payload->date('deadline_at'),
            expirationAt: $payload->date('expiration_at'),
            files: $files === null ? null : Files::fromPayload($files),
            signatures: array_map(Signature::fromPayload(...), $payload->list('signatures')),
        );
    }

    /**
     * The signer with this public id, if they are on the document.
     */
    public function signature(string $publicId): ?Signature
    {
        foreach ($this->signatures as $signature) {
            if ($signature->publicId === $publicId) {
                return $signature;
            }
        }

        return null;
    }

    /**
     * Whether every signer has signed.
     */
    public function isSigned(): bool
    {
        return $this->signatures !== []
            && array_all($this->signatures, fn(Signature $signature): bool => $signature->hasSigned());
    }
}
