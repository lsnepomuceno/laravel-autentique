<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Data\{EmailTemplate, Organization, Page};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The organizations the token's owner belongs to, their groups, and the
 * account's email templates.
 *
 * Dashboard document templates are not here because the API does not offer
 * them; the guide shows the documented alternative.
 */
final readonly class Organizations
{
    public function __construct(private GraphQLClient $client) {}

    /**
     * The organization the token acts in, with its groups.
     *
     * @throws AutentiqueException
     */
    public function current(): Organization
    {
        $data = Payload::of($this->client->send(Operation::Organization));

        return Organization::fromPayload($data->object(Operation::Organization->field()) ?? Payload::of([]));
    }

    /**
     * Every organization the token's owner belongs to. Their `$id` is what
     * `forOrganization()`, `sign()` and `transfer()` take.
     *
     * @return list<Organization>
     *
     * @throws AutentiqueException
     */
    public function list(): array
    {
        $data = Payload::of($this->client->send(Operation::Organizations));

        return array_map(Organization::fromPayload(...), $data->list(Operation::Organizations->field()));
    }

    /**
     * A page of the account's email templates.
     *
     * @return Page<EmailTemplate>
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function emailTemplates(int $page = 1, int $perPage = 60): Page
    {
        if ($page < 1 || $perPage < 1) {
            throw new InvalidInput("Pages start at 1 and hold at least one item; page {$page} of {$perPage} given.");
        }

        $data = Payload::of($this->client->send(Operation::EmailTemplates, ['limit' => $perPage, 'page' => $page]));

        return Page::fromPayload(
            $data->object(Operation::EmailTemplates->field()) ?? Payload::of([]),
            EmailTemplate::fromPayload(...),
        );
    }
}
