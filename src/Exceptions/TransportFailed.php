<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * The request did not complete: the connection failed or timed out, or the
 * answer was not a GraphQL response (a 5xx, a proxy's error page, a body that
 * is not JSON).
 *
 * **It may have been processed.** A timeout says nothing about whether
 * Autentique acted, which is why it is never retried here
 * (docs/decisions/0009-only-a-refused-request-is-retried.md).
 */
final class TransportFailed extends RequestFailed {}
