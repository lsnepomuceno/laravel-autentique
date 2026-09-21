<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique;

use Illuminate\Support\ServiceProvider;
use LSNepomuceno\LaravelAutentique\Contracts\{Autentique, GraphQLClient};
use LSNepomuceno\LaravelAutentique\GraphQL\{Client, OperationLoader};

final class LaravelAutentiqueServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__ . '/../config/autentique.php';

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'autentique');

        // One loader per application, so every operation file is read once.
        $this->app->singleton(OperationLoader::class);

        // The one transport (docs/spec/invariants.md, rule 1). Bound to the
        // contract, so the fake can take its place.
        $this->app->singleton(GraphQLClient::class, Client::class);

        // Bound to the contract rather than the concrete class, so consuming
        // applications and tests can swap the implementation.
        $this->app->singleton(Autentique::class, AutentiqueManager::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'autentique');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_PATH => $this->app->configPath('autentique.php'),
            ], 'autentique-config');
        }
    }
}
