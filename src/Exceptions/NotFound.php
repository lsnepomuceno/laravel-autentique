<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Exceptions;

/**
 * The document, folder or signature asked for does not exist, or is not
 * visible to the token's owner.
 */
final class NotFound extends RequestFailed {}
