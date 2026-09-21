<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What happens when an automatic verification runs out of attempts. Without
 * it, the verification falls back to manual approval.
 */
enum FallbackBehavior: string
{
    case DisableFallback = 'DISABLE_FALLBACK';
}
