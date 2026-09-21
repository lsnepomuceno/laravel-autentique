<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * A value the package refused before sending anything, because Autentique
 * documents that it would refuse it too, or silently change it.
 *
 * Failing here costs nothing; failing at Autentique costs a request, and for
 * some rules a document created differently from what the caller asked.
 */
final class InvalidInput extends AutentiqueException {}
