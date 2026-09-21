<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Enums\{Action, DeliveryMethod, SignerType};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * Someone asked to act on a document, and how they are reached.
 *
 * Built through the named constructors, one per way Autentique reaches a
 * signer, so a signer can never lack an address:
 *
 * - `email()`: Autentique emails the request;
 * - `whatsapp()`, `sms()`: Autentique sends it to a phone;
 * - `link()`: nothing is sent, and the answer carries a link for you to
 *   deliver. With an email or a phone, only that address can sign through it.
 *
 * Immutable: every `with…()` returns a new signer.
 */
final readonly class Signer
{
    /**
     * @param  list<Position>  $positions
     * @param  list<SecurityVerification>  $verifications
     *
     * @throws InvalidInput
     */
    public function __construct(
        public Action $action,
        public ?string $email = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?DeliveryMethod $deliveryMethod = null,
        public array $positions = [],
        public array $verifications = [],
        public ?string $cpf = null,
        public ?SignerType $type = null,
    ) {
        if ($email === null && $name === null && $phone === null) {
            throw new InvalidInput('A signer needs an email, a name or a phone.');
        }

        if ($phone !== null && preg_match('/^\+\d{8,15}$/', $phone) !== 1) {
            throw new InvalidInput("A phone is written in international format, +5554999999999; {$phone} given.");
        }

        if ($phone !== null && $deliveryMethod === null) {
            throw new InvalidInput('A signer reached by phone needs a delivery method: WhatsApp, SMS or a link.');
        }

        $photoChecks = array_filter($verifications, fn(SecurityVerification $check): bool => $check->isPhotoCheck());

        if (count($photoChecks) > 1) {
            throw new InvalidInput('A signer can have only one of the MANUAL, UPLOAD, LIVE and PF_FACIAL verifications.');
        }
    }

    /**
     * Autentique emails the request to sign.
     *
     * @throws InvalidInput
     */
    public static function email(string $email, Action $action = Action::Sign): self
    {
        return new self($action, email: $email);
    }

    /**
     * Autentique sends the request by WhatsApp.
     *
     * @throws InvalidInput
     */
    public static function whatsapp(string $phone, Action $action = Action::Sign): self
    {
        return new self($action, phone: $phone, deliveryMethod: DeliveryMethod::Whatsapp);
    }

    /**
     * Autentique sends the request by SMS. Brazilian documents only.
     *
     * @throws InvalidInput
     */
    public static function sms(string $phone, Action $action = Action::Sign): self
    {
        return new self($action, phone: $phone, deliveryMethod: DeliveryMethod::Sms);
    }

    /**
     * Nothing is sent: the created document carries the signer's link, in
     * `$signature->link->shortLink`, for you to deliver.
     *
     * With an email or a phone, the link can only be signed by that address.
     *
     * @throws InvalidInput
     */
    public static function link(string $name, Action $action = Action::Sign, ?string $email = null, ?string $phone = null): self
    {
        $bound = $email !== null || $phone !== null;

        return new self($action, $email, $name, $phone, $bound ? DeliveryMethod::Link : null);
    }

    /**
     * @throws InvalidInput
     */
    public function withAction(Action $action): self
    {
        return new self($action, $this->email, $this->name, $this->phone, $this->deliveryMethod, $this->positions, $this->verifications, $this->cpf, $this->type);
    }

    /**
     * The signer's name, shown on the document and in the messages sent.
     *
     * @throws InvalidInput
     */
    public function withName(string $name): self
    {
        return new self($this->action, $this->email, $name, $this->phone, $this->deliveryMethod, $this->positions, $this->verifications, $this->cpf, $this->type);
    }

    /**
     * Stamps something at a position once the signer acts. Call it again for
     * every position.
     *
     * @throws InvalidInput
     */
    public function withPosition(Position $position): self
    {
        return new self($this->action, $this->email, $this->name, $this->phone, $this->deliveryMethod, [...$this->positions, $position], $this->verifications, $this->cpf, $this->type);
    }

    /**
     * Requires an additional identity check. Call it again for every check.
     *
     * @throws InvalidInput
     */
    public function withVerification(SecurityVerification $verification): self
    {
        return new self($this->action, $this->email, $this->name, $this->phone, $this->deliveryMethod, $this->positions, [...$this->verifications, $verification], $this->cpf, $this->type);
    }

    /**
     * Only the holder of this CPF can sign.
     *
     * @throws InvalidInput
     */
    public function withCpf(string $cpf): self
    {
        return new self($this->action, $this->email, $this->name, $this->phone, $this->deliveryMethod, $this->positions, $this->verifications, $cpf, $this->type);
    }

    /**
     * Signs with a qualified certificate, while others on the same document may
     * sign electronically. Corporate plan only.
     *
     * @throws InvalidInput
     */
    public function qualified(): self
    {
        return new self($this->action, $this->email, $this->name, $this->phone, $this->deliveryMethod, $this->positions, $this->verifications, $this->cpf, SignerType::Qualified);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'delivery_method' => $this->deliveryMethod?->value,
            'action' => $this->action->value,
            'positions' => array_map(fn(Position $position): array => $position->toArray(), $this->positions),
            'security_verifications' => array_map(fn(SecurityVerification $check): array => $check->toArray(), $this->verifications),
            'configs' => $this->cpf === null ? [] : ['cpf' => $this->cpf],
            'type' => $this->type?->value,
        ], fn(mixed $value): bool => $value !== null && $value !== []);
    }
}
