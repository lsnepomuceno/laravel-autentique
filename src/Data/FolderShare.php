<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Enums\FolderRole;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Someone a folder is shared with, a group, or anyone holding its link.
 *
 * `$id` is what `Api\Folders::changeRole()` takes.
 */
final readonly class FolderShare
{
    public function __construct(
        public ?string $id,
        public ?FolderRole $role,
        public ?bool $byLink,
        public ?User $user,
        public ?Group $group,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $user = $payload->object('user');
        $group = $payload->object('group');

        return new self(
            id: $payload->nullableString('id'),
            role: $payload->enum('role', FolderRole::class),
            byLink: $payload->nullableBool('is_by_link'),
            user: $user === null || ! $user->has('id') ? null : User::fromPayload($user),
            group: $group === null || ! $group->has('id') ? null : Group::fromPayload($group),
        );
    }
}
