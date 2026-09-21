<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use LSNepomuceno\LaravelAutentique\Commands\{CheckCommand, SchemaCommand};
use LSNepomuceno\LaravelAutentique\Contracts\{Autentique, GraphQLClient};
use LSNepomuceno\LaravelAutentique\GraphQL\{Client, OperationLoader};
use LSNepomuceno\LaravelAutentique\Webhooks\{VerifyAutentiqueSignature, WebhookController};

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

        $this->registerWebhookRoute();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_PATH => $this->app->configPath('autentique.php'),
            ], 'autentique-config');

            $this->commands([
                CheckCommand::class,
                SchemaCommand::class,
            ]);
        }
    }

    /**
     * The webhook route, when `autentique.webhooks.path` names one.
     *
     * Registered with only the middleware the configuration lists, never the
     * `web` group: Autentique sends no CSRF token, and the signature check is
     * what authenticates the request.
     */
    private function registerWebhookRoute(): void
    {
        $config = $this->app->make(Repository::class);
        $path = $config->get('autentique.webhooks.path');

        if (! is_string($path) || $path === '') {
            return;
        }

        $middleware = $config->get('autentique.webhooks.middleware');

        $this->app->make(Router::class)
            ->post($path, WebhookController::class)
            ->middleware([...(is_array($middleware) ? array_values(array_filter($middleware, is_string(...))) : []), VerifyAutentiqueSignature::class])
            ->name('autentique.webhook');
    }
}
