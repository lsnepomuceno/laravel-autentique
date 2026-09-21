<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * Every signature asked to be resent was resent too recently, so Autentique
 * sent nothing (`too_many_resent_emails`).
 *
 * When only some of them were, Autentique skips those and resends the rest,
 * and no exception is thrown.
 */
final class ResendThrottled extends RequestFailed
{
    public static function from(GraphQLError $error): self
    {
        return new self(
            'Every signature asked to be resent was resent recently. Wait before resending again.',
            $error->operation,
            $error->status,
            $error->requestId,
            $error->errors,
            $error,
        );
    }
}
