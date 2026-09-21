<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use LSNepomuceno\LaravelAutentique\Contracts\FileSource;
use LSNepomuceno\LaravelAutentique\Data\Document;
use LSNepomuceno\LaravelAutentique\Data\Input\{DocumentConfig, Expiration, Locale, NewDocument, Signer};
use LSNepomuceno\LaravelAutentique\Enums\{DocumentType, Footer, FooterType, Reminder, WhatsappTemplate};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};
use LSNepomuceno\LaravelAutentique\Io\{PathFile, UploadedFileSource};

/**
 * A document being put together, sent with `send()`.
 *
 * ```php
 * Autentique::newDocument('Service agreement')
 *     ->file($request->file('contract'))
 *     ->signer(Signer::email('ana@example.com')->withPosition(Position::signature(50, 90)))
 *     ->signer(Signer::whatsapp('+5554999999999', Action::Approve))
 *     ->reminder(Reminder::Weekly)
 *     ->send();
 * ```
 *
 * A builder, so it is mutable and every method returns the same instance. What
 * it builds is immutable: `send()` makes a `Data\Input\NewDocument`, which
 * refuses the combinations Autentique refuses, and hands it to
 * `Api\Documents::create()`.
 */
final class PendingDocument
{
    private ?FileSource $file = null;

    /** @var list<Signer> */
    private array $signers = [];

    private ?bool $sandbox = null;

    private ?string $folderId = null;

    private ?int $organizationId = null;

    private DocumentType $type = DocumentType::Default;

    private ?string $message = null;

    private ?Reminder $reminder = null;

    private ?bool $sortable = null;

    private ?bool $refusable = null;

    private ?bool $stopOnRejected = null;

    private ?bool $qualified = null;

    private ?bool $scrollingRequired = null;

    private ?Footer $footer = null;

    private ?FooterType $footerType = null;

    private ?bool $newSignatureStyle = null;

    private ?bool $showAuditPage = null;

    private ?bool $ignoreCpf = null;

    private ?bool $ignoreBirthdate = null;

    private ?int $emailTemplateId = null;

    private ?DateTimeInterface $deadline = null;

    private ?string $replyTo = null;

    private ?WhatsappTemplate $whatsappTemplate = null;

    private ?Expiration $expiration = null;

    private ?DocumentConfig $configs = null;

    private ?Locale $locale = null;
    /** @var list<string> */
    private array $watchers = [];

    /** @var list<string> */
    private array $cc = [];

    public function __construct(
        private readonly Documents $documents,
        private readonly string $name,
    ) {}

    /**
     * The file to sign: a path, an upload from the current request, or any
     * `FileSource`, such as `Autentique::fromDisk()`.
     *
     * @throws InvalidInput
     */
    public function file(string|UploadedFile|FileSource $file): self
    {
        $this->file = match (true) {
            is_string($file) => new PathFile($file),
            $file instanceof UploadedFile => new UploadedFileSource($file),
            default => $file,
        };

        return $this;
    }

    public function signer(Signer $signer): self
    {
        $this->signers[] = $signer;

        return $this;
    }

    /**
     * @param  iterable<Signer>  $signers
     */
    public function signers(iterable $signers): self
    {
        foreach ($signers as $signer) {
            $this->signer($signer);
        }

        return $this;
    }

    /**
     * A test document: not billed, not legally valid, deleted by Autentique
     * after a few days. Without a call, `autentique.sandbox` decides.
     */
    public function sandbox(bool $sandbox = true): self
    {
        $this->sandbox = $sandbox;

        return $this;
    }

    public function inFolder(string $folderId): self
    {
        $this->folderId = $folderId;

        return $this;
    }

    /**
     * Creates the document in another of the account's organizations.
     */
    public function forOrganization(int $organizationId): self
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    /**
     * Signed inside WhatsApp, from a Markdown file.
     */
    public function whatsappFlow(): self
    {
        $this->type = DocumentType::WhatsappFlow;

        return $this;
    }

    /**
     * The message sent to signers in the request email.
     */
    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Reminds signers who have not acted. It makes the document refusable.
     */
    public function reminder(Reminder $reminder): self
    {
        $this->reminder = $reminder;

        return $this;
    }

    /**
     * Signers sign in the order they were added.
     */
    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * Signers may refuse to sign.
     */
    public function refusable(bool $refusable = true): self
    {
        $this->refusable = $refusable;

        return $this;
    }

    /**
     * Once someone refuses, nobody else can sign. Needs a refusable document.
     */
    public function stopOnRejected(bool $stop = true): self
    {
        $this->stopOnRejected = $stop;

        return $this;
    }

    /**
     * Every signer signs with a qualified certificate.
     */
    public function qualified(bool $qualified = true): self
    {
        $this->qualified = $qualified;

        return $this;
    }

    /**
     * Signers must scroll through the whole document before signing.
     */
    public function scrollingRequired(bool $required = true): self
    {
        $this->scrollingRequired = $required;

        return $this;
    }

    public function footer(Footer $footer, ?FooterType $type = null): self
    {
        $this->footer = $footer;
        $this->footerType = $type ?? $this->footerType;

        return $this;
    }

    public function newSignatureStyle(bool $new = true): self
    {
        $this->newSignatureStyle = $new;

        return $this;
    }

    /**
     * Leaves out the final audit page. Needs the new signature style.
     */
    public function withoutAuditPage(): self
    {
        $this->showAuditPage = false;

        return $this;
    }

    /**
     * Nobody is asked for a CPF.
     */
    public function ignoreCpf(bool $ignore = true): self
    {
        $this->ignoreCpf = $ignore;

        return $this;
    }

    /**
     * Nobody is asked for a date of birth.
     */
    public function ignoreBirthdate(bool $ignore = true): self
    {
        $this->ignoreBirthdate = $ignore;

        return $this;
    }

    /**
     * One of the account's email templates, from `organizations()->emailTemplates()`.
     */
    public function emailTemplate(int $templateId): self
    {
        $this->emailTemplateId = $templateId;

        return $this;
    }

    /**
     * No one can sign after this moment.
     */
    public function deadline(DateTimeInterface $deadline): self
    {
        $this->deadline = $deadline;

        return $this;
    }

    /**
     * Where signers' replies to the request email go.
     */
    public function replyTo(string $email): self
    {
        $this->replyTo = $email;

        return $this;
    }

    public function whatsappTemplate(WhatsappTemplate $template): self
    {
        $this->whatsappTemplate = $template;

        return $this;
    }

    /**
     * People who follow the document without signing. Professional plan.
     *
     * @param  list<string>  $emails
     */
    public function watchers(array $emails): self
    {
        $this->watchers = $emails;

        return $this;
    }

    /**
     * People who receive the signed document once everyone has signed.
     *
     * @param  list<string>  $emails
     */
    public function cc(array $emails): self
    {
        $this->cc = $emails;

        return $this;
    }

    public function expiration(Expiration $expiration): self
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function configs(DocumentConfig $configs): self
    {
        $this->configs = $configs;

        return $this;
    }

    public function locale(Locale $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * The document as it would be sent, refusing what Autentique would refuse.
     *
     * @throws InvalidInput
     */
    public function toNewDocument(): NewDocument
    {
        return new NewDocument(
            name: $this->name,
            message: $this->message,
            reminder: $this->reminder,
            sortable: $this->sortable,
            footer: $this->footer,
            footerType: $this->footerType,
            refusable: $this->refusable,
            qualified: $this->qualified,
            scrollingRequired: $this->scrollingRequired,
            stopOnRejected: $this->stopOnRejected,
            newSignatureStyle: $this->newSignatureStyle,
            showAuditPage: $this->showAuditPage,
            ignoreCpf: $this->ignoreCpf,
            ignoreBirthdate: $this->ignoreBirthdate,
            emailTemplateId: $this->emailTemplateId,
            deadline: $this->deadline,
            replyTo: $this->replyTo,
            whatsappTemplate: $this->whatsappTemplate,
            watchers: $this->watchers,
            cc: $this->cc,
            expiration: $this->expiration,
            configs: $this->configs,
            locale: $this->locale,
        );
    }

    /**
     * Creates the document and sends it to its signers.
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function send(): Document
    {
        if ($this->file === null) {
            throw new InvalidInput("The document {$this->name} has no file: call file() before send().");
        }

        return $this->documents->create(
            document: $this->toNewDocument(),
            signers: $this->signers,
            file: $this->file,
            sandbox: $this->sandbox,
            folderId: $this->folderId,
            organizationId: $this->organizationId,
            type: $this->type,
        );
    }
}
