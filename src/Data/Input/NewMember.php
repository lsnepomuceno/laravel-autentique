<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use DateTimeInterface;
use LSNepomuceno\LaravelAutentique\Enums\{Language, MemberPermission};

/**
 * A member to add to a child organization, or what to change on one.
 *
 * Without a group, the member lands in `Sem grupo` and `$permissions` apply:
 * each one listed is granted.
 */
final readonly class NewMember
{
    /**
     * @param  list<MemberPermission>  $permissions
     */
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?DateTimeInterface $birthday = null,
        public ?string $cpf = null,
        public ?string $company = null,
        public ?int $groupId = null,
        public ?Language $language = null,
        public array $permissions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $permissions = [];

        foreach ($this->permissions as $permission) {
            $permissions[$permission->value] = true;
        }

        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'birthday' => $this->birthday?->format('Y-m-d'),
            'cpf' => $this->cpf,
            'company' => $this->company,
            'group_id' => $this->groupId,
            'locale' => $this->language === null ? null : ['language' => $this->language->value],
            'permissions' => $permissions,
        ], fn(mixed $value): bool => $value !== null && $value !== []);
    }
}
