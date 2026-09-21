<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Facades;

use Illuminate\Support\Facades\Facade;
use LSNepomuceno\LaravelAutentique\Contracts\Autentique as AutentiqueContract;

/**
 * @see \LSNepomuceno\LaravelAutentique\AutentiqueManager
 */
final class Autentique extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return AutentiqueContract::class;
    }
}
