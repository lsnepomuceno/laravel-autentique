<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Webhooks;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\{JsonResponse, Request};
use LSNepomuceno\LaravelAutentique\Exceptions\MissingWebhookSecret;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses a webhook whose signature does not match, before any application
 * code runs.
 *
 * Usable on the application's own route, and applied to the one the package
 * registers when `autentique.webhooks.path` is set.
 */
final readonly class VerifyAutentiqueSignature
{
    public function __construct(private Repository $config) {}

    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws MissingWebhookSecret
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->config->get('autentique.webhooks.secret');

        if (! is_string($secret) || $secret === '') {
            throw MissingWebhookSecret::make();
        }

        if (! SignatureVerifier::verify($request->getContent(), $request->header(SignatureVerifier::HEADER), $secret)) {
            return new JsonResponse(['message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }
}
