<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;

/**
 * What `updateOrganization` changes on a child organization.
 */
final readonly class ChildOrganizationChanges
{
    /**
     * @param  ?string  $webhookUrl  The deprecated per organization webhook. New integrations register an endpoint instead.
     *
     * @throws InvalidInput
     */
    public function __construct(
        public ?string $name = null,
        public ?string $cnpj = null,
        public ?string $webhookUrl = null,
    ) {
        if ($this->toArray() === []) {
            throw new InvalidInput('An update needs at least one change.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'cnpj' => $this->cnpj,
            'settings' => $this->webhookUrl === null ? null : ['webhook_url' => $this->webhookUrl],
        ], fn(mixed $value): bool => $value !== null);
    }
}
