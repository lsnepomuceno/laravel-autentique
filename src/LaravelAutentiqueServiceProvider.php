<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique;

use Illuminate\Support\ServiceProvider;
use LSNepomuceno\LaravelAutentique\Contracts\Autentique;

final class LaravelAutentiqueServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__ . '/../config/autentique.php';

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'autentique');

        // Bound to the contract rather than the concrete class, so consuming
        // applications and tests can swap the implementation.
        $this->app->singleton(Autentique::class, AutentiqueManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_PATH => $this->app->configPath('autentique.php'),
            ], 'autentique-config');
        }
    }
}
