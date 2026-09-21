<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use DateTimeInterface;
use LSNepomuceno\LaravelAutentique\Enums\Language;

/**
 * What a signer who has no Autentique account yet finds already filled in.
 * Corporate plan.
 */
final readonly class PrefilledFields
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $cpf = null,
        public ?DateTimeInterface $birthdate = null,
        public ?string $country = null,
        public ?Language $language = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'birthdate' => $this->birthdate?->format('Y-m-d'),
            'country' => $this->country,
            'language' => $this->language?->value,
        ], fn(mixed $value): bool => $value !== null);
    }
}
