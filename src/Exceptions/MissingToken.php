<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * No token is configured, so no request was sent.
 */
final class MissingToken extends AutentiqueException
{
    public static function make(): self
    {
        return new self('No Autentique token is configured. Set AUTENTIQUE_TOKEN in the environment.');
    }
}
