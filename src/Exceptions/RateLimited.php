<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * Autentique refused the request with HTTP 429, and the retries the
 * configuration allows were spent (docs/decisions/0009-only-a-refused-request-is-retried.md).
 *
 * Nothing was processed, so the request is safe to send again after
 * `$retryAfter` seconds, when Autentique said how long.
 */
final class RateLimited extends RequestFailed
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        ?string $operation = null,
        ?string $requestId = null,
    ) {
        parent::__construct($message, $operation, 429, $requestId);
    }
}
