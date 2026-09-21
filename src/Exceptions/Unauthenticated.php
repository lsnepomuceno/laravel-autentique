<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * Autentique did not accept the token: HTTP 401, or an `Unauthorized` error
 * with HTTP 200 when an OAuth token lacks the scope an operation needs.
 */
final class Unauthenticated extends RequestFailed {}
