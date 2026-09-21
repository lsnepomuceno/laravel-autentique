<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Contracts;

use LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

/**
 * Sends an operation to Autentique and returns its `data`.
 *
 * The default implementation is `LSNepomuceno\LaravelAutentique\GraphQL\Client`,
 * the only class in the package that reaches the network
 * (docs/spec/invariants.md, rule 1). It is a contract so the fake can stand in
 * for it.
 */
interface GraphQLClient
{
    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed> The response's `data`.
     *
     * @throws AutentiqueException
     */
    public function send(Operation $operation, array $variables = [], ?FileSource $file = null): array;

    /**
     * Sends a document the package does not ship, with variables.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed> The response's `data`.
     *
     * @throws AutentiqueException
     */
    public function raw(string $document, array $variables = []): array;

    /**
     * Posts a form to Autentique's OAuth token endpoint and returns the tokens
     * it answered with.
     *
     * Not GraphQL, and here all the same, so the token exchange goes through
     * the one transport too (docs/decisions/0011-oauth-goes-through-the-same-client.md).
     *
     * @param  array<string, string>  $form
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    public function oauthToken(#[\SensitiveParameter] array $form): array;

    /**
     * The same client, sending another token.
     */
    public function withToken(#[\SensitiveParameter] string $token): self;
}
