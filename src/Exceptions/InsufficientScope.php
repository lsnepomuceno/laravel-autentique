<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * Autentique accepted the OAuth token, but it lacks the scope the operation
 * needs, or the operation is not available through OAuth: `Unauthorized` with
 * HTTP 200 (docs/decisions/0011-oauth-goes-through-the-same-client.md).
 *
 * Refreshing does not help; authorizing again with more scopes does.
 */
final class InsufficientScope extends RequestFailed {}
