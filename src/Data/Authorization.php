<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

/**
 * The start of an OAuth authorization: where to send the user, and the two
 * values to keep in their session until they come back.
 */
final readonly class Authorization
{
    public function __construct(
        public string $url,
        /** Compared with the one that comes back, to refuse a forged callback. */
        public string $state,
        /** The PKCE verifier, sent with the code to prove the same client asked. */
        #[\SensitiveParameter]
        public string $verifier,
    ) {}
}
