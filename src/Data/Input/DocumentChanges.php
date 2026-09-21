<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use LSNepomuceno\LaravelAutentique\Enums\{Footer, Reminder};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * What `updateDocument` changes on an existing document.
 *
 * Only what is set is sent, and everything else stays as it is. The same rules
 * as on creation apply to what is set together.
 */
final readonly class DocumentChanges
{
    /**
     * @param  ?list<string>  $watchers  Replaces the watchers. Null leaves them alone.
     * @param  ?list<string>  $cc  Replaces the copied addresses. Null leaves them alone.
     *
     * @throws InvalidInput
     */
    public function __construct(
        public ?string $name = null,
        public ?string $message = null,
        public ?Reminder $reminder = null,
        public ?bool $sortable = null,
        public ?Footer $footer = null,
        public ?bool $refusable = null,
        public ?bool $stopOnRejected = null,
        public ?bool $scrollingRequired = null,
        public ?bool $newSignatureStyle = null,
        public ?bool $showAuditPage = null,
        public ?bool $ignoreCpf = null,
        public ?int $emailTemplateId = null,
        public ?DateTimeInterface $deadline = null,
        public ?array $watchers = null,
        public ?array $cc = null,
        public ?Expiration $expiration = null,
        public ?DocumentConfig $configs = null,
    ) {
        if ($this->toArray() === []) {
            throw new InvalidInput('An update needs at least one change.');
        }

        if ($name !== null && trim($name) === '') {
            throw new InvalidInput('A document cannot be renamed to nothing.');
        }

        if ($reminder !== null && $refusable === false) {
            throw new InvalidInput('A reminder makes the document refusable: Autentique would ignore refusable: false.');
        }

        if ($showAuditPage === false && $newSignatureStyle === false) {
            throw new InvalidInput('The audit page can only be left out with the new signature style.');
        }
    }

    /**
     * Blocks signing from now on. Deleting a document does not: it only moves
     * it to the trash, and whoever still has the link can sign.
     *
     * @throws InvalidInput
     */
    public static function block(): self
    {
        return new self(deadline: CarbonImmutable::now());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'message' => $this->message,
            'reminder' => $this->reminder?->value,
            'sortable' => $this->sortable,
            'footer' => $this->footer?->value,
            'refusable' => $this->refusable,
            'stop_on_rejected' => $this->stopOnRejected,
            'scrolling_required' => $this->scrollingRequired,
            'new_signature_style' => $this->newSignatureStyle,
            'show_audit_page' => $this->showAuditPage,
            'ignore_cpf' => $this->ignoreCpf,
            'email_template_id' => $this->emailTemplateId,
            'deadline_at' => NewDocument::moment($this->deadline),
            'watchers' => $this->watchers,
            'cc' => $this->cc === null ? null : array_map(fn(string $email): array => ['email' => $email], $this->cc),
            'expiration' => $this->expiration?->toArray(),
            'configs' => $this->configs?->toArray(),
        ], fn(mixed $value): bool => $value !== null);
    }
}
