<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Testing;

use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

/**
 * One call the fake received, as the real client would have sent it.
 */
final readonly class SentOperation
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        /** Null for a raw query sent through `Autentique::query()`. */
        public ?Operation $operation,
        public array $variables,
        /** The name of the uploaded file, when there was one. */
        public ?string $fileName = null,
        /** The document sent, for a raw query. */
        public ?string $query = null,
        /** The token it was sent with, when it was not the configured one. */
        #[\SensitiveParameter]
        public ?string $token = null,
    ) {}
}
