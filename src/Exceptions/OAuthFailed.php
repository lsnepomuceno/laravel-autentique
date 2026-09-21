<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * An OAuth step failed: the user denied access, the `state` came back wrong,
 * or the token endpoint refused a code or a refresh token.
 *
 * `$error` is the OAuth error code, `access_denied` or `invalid_grant`, when
 * there is one. After `invalid_grant` on a refresh, the only way back is to
 * authorize again.
 */
final class OAuthFailed extends RequestFailed
{
    public function __construct(
        string $message,
        public readonly ?string $error = null,
        ?int $status = null,
        ?string $requestId = null,
    ) {
        parent::__construct($message, 'oauth', $status, $requestId);
    }
}
