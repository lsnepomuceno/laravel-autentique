<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Tests;

use LSNepomuceno\LaravelAutentique\LaravelAutentiqueServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return list<class-string<\Illuminate\Support\ServiceProvider>>
     */
    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [
            LaravelAutentiqueServiceProvider::class,
        ];
    }
}
