<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Enums\EmailTemplateType;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * One of the account's email templates. `$id` is what
 * `PendingDocument::emailTemplate()` takes.
 */
final readonly class EmailTemplate
{
    /**
     * @param  list<string>  $colors
     */
    public function __construct(
        public int $id,
        public ?string $name,
        public ?EmailTemplateType $type,
        public ?string $text,
        public ?string $sender,
        public array $colors,
        public ?string $template,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->int('id'),
            name: $payload->nullableString('name'),
            type: $payload->enum('type', EmailTemplateType::class),
            text: $payload->nullableString('email.text'),
            sender: $payload->nullableString('email.sender'),
            colors: $payload->strings('email.colors'),
            template: $payload->nullableString('email.template'),
        );
    }
}
