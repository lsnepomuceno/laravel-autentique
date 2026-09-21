<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use LSNepomuceno\LaravelAutentique\LaravelAutentiqueServiceProvider;
use LSNepomuceno\LaravelAutentique\Webhooks\SignatureVerifier;
use Orchestra\Testbench\TestCase as Orchestra;
use Symfony\Component\HttpFoundation\Response;

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
     * The webhook route is registered when the application boots, so its
     * configuration is set here rather than in a test.
     */
    #[\Override]
    protected function defineEnvironment($app): void
    {
        $app['config']->set('autentique.webhooks.path', 'webhooks/autentique');
        $app['config']->set('autentique.webhooks.secret', 'the-webhook-secret');
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

    /**
     * Posts a body to the package's webhook route, signed with the configured
     * secret unless a signature is given.
     *
     * @return TestResponse<Response>
     */
    public function postWebhook(string $body, ?string $signature = null): TestResponse
    {
        $signature ??= SignatureVerifier::sign($body, 'the-webhook-secret');

        return $this->call('POST', '/webhooks/autentique', server: [
            'HTTP_X_AUTENTIQUE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], content: $body);
    }
}
