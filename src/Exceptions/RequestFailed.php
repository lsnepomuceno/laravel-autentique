<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

use LSNepomuceno\LaravelAutentique\Data\ApiError;
use Throwable;

/**
 * A request that reached Autentique, or tried to, and did not succeed.
 *
 * Every subclass names one fault (docs/decisions/0005-exceptions-name-the-real-fault.md).
 * What they share is what support needs: the operation, the HTTP status, the
 * request id Autentique returns in `X-Attq-Request-Id`, and every entry of the
 * `errors` array rather than only the first.
 *
 * None of them ever carries the token (docs/spec/invariants.md, rule 3).
 */
abstract class RequestFailed extends AutentiqueException
{
    /**
     * @param  list<ApiError>  $errors
     */
    public function __construct(
        string $message,
        public readonly ?string $operation = null,
        public readonly ?int $status = null,
        public readonly ?string $requestId = null,
        public readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
