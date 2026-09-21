<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique;

use LSNepomuceno\LaravelAutentique\Api\Account;
use LSNepomuceno\LaravelAutentique\Contracts\{Autentique, GraphQLClient};

/**
 * The default implementation of the contract, bound as a singleton.
 */
final readonly class AutentiqueManager implements Autentique
{
    public function __construct(private GraphQLClient $client) {}

    #[\Override]
    public function account(): Account
    {
        return new Account($this->client);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    #[\Override]
    public function query(string $graphql, array $variables = []): array
    {
        return $this->client->raw($graphql, $variables);
    }
}
