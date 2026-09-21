<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use LSNepomuceno\LaravelAutentique\Enums\{Footer, FooterType, Reminder, WhatsappTemplate};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * Everything `createDocument` takes as `document`, other than the file and the
 * signers.
 *
 * Every argument is optional except the name, and one left null is not sent,
 * so Autentique's own default applies. The combinations Autentique documents
 * as refused, or silently changed, are refused here instead, before a request
 * is spent (docs/decisions/0008-responses-are-typed-value-objects.md).
 */
final readonly class NewDocument
{
    /**
     * @param  list<string>  $watchers  Emails of people who follow the document without signing. Professional plan.
     * @param  list<string>  $cc  Emails that receive the signed document once everyone has signed.
     *
     * @throws InvalidInput
     */
    public function __construct(
        public string $name,
        public ?string $message = null,
        public ?Reminder $reminder = null,
        public ?bool $sortable = null,
        public ?Footer $footer = null,
        public ?FooterType $footerType = null,
        public ?bool $refusable = null,
        public ?bool $qualified = null,
        public ?bool $scrollingRequired = null,
        public ?bool $stopOnRejected = null,
        public ?bool $newSignatureStyle = null,
        public ?bool $showAuditPage = null,
        public ?bool $ignoreCpf = null,
        public ?bool $ignoreBirthdate = null,
        public ?int $emailTemplateId = null,
        public ?DateTimeInterface $deadline = null,
        public ?string $replyTo = null,
        public ?WhatsappTemplate $whatsappTemplate = null,
        public array $watchers = [],
        public array $cc = [],
        public ?Expiration $expiration = null,
        public ?DocumentConfig $configs = null,
        public ?Locale $locale = null,
    ) {
        if (trim($name) === '') {
            throw new InvalidInput('A document needs a name.');
        }

        if ($reminder !== null && $refusable === false) {
            throw new InvalidInput('A reminder makes the document refusable: Autentique would ignore refusable: false.');
        }

        if ($stopOnRejected === true && $refusable !== true) {
            throw new InvalidInput('Stopping on a rejection needs a refusable document: set refusable as well.');
        }

        if ($showAuditPage === false && $newSignatureStyle !== true) {
            throw new InvalidInput('The audit page can only be left out with the new signature style.');
        }

        if ($configs?->pdfa === true && ($configs->lockUserData === false || $newSignatureStyle === false)) {
            throw new InvalidInput('PDF/A keeps user data locked and uses the new signature style: Autentique would override both.');
        }
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
            'footer_type' => $this->footerType?->value,
            'refusable' => $this->refusable,
            'qualified' => $this->qualified,
            'scrolling_required' => $this->scrollingRequired,
            'stop_on_rejected' => $this->stopOnRejected,
            'new_signature_style' => $this->newSignatureStyle,
            'show_audit_page' => $this->showAuditPage,
            'ignore_cpf' => $this->ignoreCpf,
            'ignore_birthdate' => $this->ignoreBirthdate,
            'email_template_id' => $this->emailTemplateId,
            'deadline_at' => self::moment($this->deadline),
            'reply_to' => $this->replyTo,
            'whatsapp_template' => $this->whatsappTemplate?->value,
            'watchers' => $this->watchers,
            'cc' => array_map(fn(string $email): array => ['email' => $email], $this->cc),
            'expiration' => $this->expiration?->toArray(),
            'configs' => $this->configs?->toArray(),
            'locale' => $this->locale?->toArray(),
        ], fn(mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * A moment as Autentique takes it: ISO 8601 in UTC, with milliseconds,
     * `2023-11-24T02:59:59.999Z`.
     */
    public static function moment(?DateTimeInterface $moment): ?string
    {
        return $moment === null ? null : CarbonImmutable::instance($moment)->utc()->format('Y-m-d\TH:i:s.v\Z');
    }
}
