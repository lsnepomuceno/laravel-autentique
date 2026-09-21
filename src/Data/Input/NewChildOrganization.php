<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

/**
 * A child organization to create. It gets the groups `Administrador` and
 * `Sem grupo` by default.
 */
final readonly class NewChildOrganization
{
    /**
     * @param  list<int>  $defaultEmailTemplateIds  One default per template type: the request, and the notice of completion.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $cnpj = null,
        public ?Locale $locale = null,
        public array $defaultEmailTemplateIds = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'cnpj' => $this->cnpj,
            'locale' => $this->locale?->toArray(),
            'default_email_templates_ids' => $this->defaultEmailTemplateIds,
        ], fn(mixed $value): bool => $value !== null && $value !== []);
    }
}
