<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * Autentique answered with an `errors` array this package has no narrower
 * exception for, or with no `data` at all.
 *
 * Every error is kept in `$errors`, with its code when this package knows it.
 */
final class GraphQLError extends RequestFailed {}
