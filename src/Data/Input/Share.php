<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data\Input;

use LSNepomuceno\LaravelAutentique\Enums\FolderRole;

/**
 * Whom to share a folder with, and what they may do in it.
 */
final readonly class Share
{
    private function __construct(
        public FolderRole $role,
        public ?string $email = null,
        public ?string $groupId = null,
    ) {}

    public static function withEmail(string $email, FolderRole $role = FolderRole::Viewer): self
    {
        return new self($role, email: $email);
    }

    public static function withGroup(string $groupId, FolderRole $role = FolderRole::Viewer): self
    {
        return new self($role, groupId: $groupId);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'group_id' => $this->groupId,
            'role' => $this->role->value,
        ], fn(mixed $value): bool => $value !== null);
    }
}
