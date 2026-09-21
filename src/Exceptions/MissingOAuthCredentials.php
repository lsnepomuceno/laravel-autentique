<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * OAuth needs the application's client id, secret and redirect URI, and one of
 * them is not configured.
 */
final class MissingOAuthCredentials extends AutentiqueException
{
    public static function for(string $key): self
    {
        return new self("OAuth needs autentique.oauth.{$key}. Set AUTENTIQUE_OAUTH_" . strtoupper($key) . ' in the environment.');
    }
}
