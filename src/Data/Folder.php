<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Enums\Context;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A folder, as the operation that returns it selects it.
 *
 * `$subfolders`, `$parent`, `$shares`, `$hasPassword` and `$linkIdentifier`
 * are filled by the operations that ask for them, `find()` among them, and are
 * empty or null otherwise.
 */
final readonly class Folder
{
    /**
     * @param  list<FolderSummary>  $subfolders
     * @param  list<FolderShare>  $shares
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $path,
        public ?Context $context,
        public ?int $childrenCount,
        public ?bool $isEditor,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public ?FolderSummary $parent = null,
        public array $subfolders = [],
        public array $shares = [],
        public ?bool $hasPassword = null,
        public ?string $linkIdentifier = null,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $parent = $payload->object('parentFolder');

        return new self(
            id: $payload->string('id'),
            name: $payload->string('name'),
            path: $payload->nullableString('path'),
            context: $payload->enum('context', Context::class),
            childrenCount: $payload->nullableInt('children_counter'),
            isEditor: $payload->nullableBool('is_editor'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
            parent: $parent === null || ! $parent->has('id') ? null : FolderSummary::fromPayload($parent),
            subfolders: array_map(FolderSummary::fromPayload(...), $payload->list('subfolders')),
            shares: array_map(FolderShare::fromPayload(...), $payload->list('shares')),
            hasPassword: $payload->nullableBool('has_password'),
            linkIdentifier: $payload->nullableString('link_identifier'),
        );
    }
}
