<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * A webhook arrived and no secret is configured to verify it against, so it
 * was refused. Thrown rather than answered, so it reaches the application's
 * error tracker instead of passing as a stream of 401s.
 */
final class MissingWebhookSecret extends AutentiqueException
{
    public static function make(): self
    {
        return new self('No Autentique webhook secret is configured. Set AUTENTIQUE_WEBHOOK_SECRET in the environment.');
    }
}
