<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Data\User;
use LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The account the token belongs to.
 */
final readonly class Account
{
    public function __construct(private GraphQLClient $client) {}

    /**
     * Who the token belongs to, their plan and their organization.
     *
     * @throws AutentiqueException
     */
    public function me(): User
    {
        $data = Payload::of($this->client->send(Operation::Me));

        return User::fromPayload($data->object(Operation::Me->field()) ?? Payload::of([]));
    }
}
