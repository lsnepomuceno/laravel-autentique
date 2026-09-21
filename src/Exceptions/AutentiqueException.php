<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

use RuntimeException;

/**
 * The base of every exception this package throws, so a caller can catch all of
 * them in one place (docs/decisions/0005-exceptions-name-the-real-fault.md).
 *
 * Abstract: what is thrown always names the fault that occurred.
 */
abstract class AutentiqueException extends RuntimeException {}
