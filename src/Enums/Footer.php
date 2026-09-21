<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * Where the signature footer is stamped on each page.
 */
enum Footer: string
{
    case Bottom = 'BOTTOM';
    case Left = 'LEFT';
    case Right = 'RIGHT';
}
