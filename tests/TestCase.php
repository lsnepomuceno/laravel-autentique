<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Tests;

use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\LaravelAutentiqueServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Refuses every request nobody faked, so the suite never reaches
     * Autentique (docs/spec/invariants.md, rule 5).
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

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
