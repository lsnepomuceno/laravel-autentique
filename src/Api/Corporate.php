<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use DateTimeInterface;
use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Data\{ApiUsage,
    ChildOrganization,
    CorporatePlan,
    OrganizationMember,
    OrganizationPlan,
    Subscription,
    WebhookEndpoint};
use LSNepomuceno\LaravelAutentique\Data\Input\{ChildOrganizationChanges, NewChildOrganization, NewMember, NewSubscriptionPlan};
use LSNepomuceno\LaravelAutentique\Enums\{CustomPlan, WebhookEndpointType, WebhookEventType, WebhookFormat};
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The Corporate plan's extension of the API, on its own endpoint: child
 * organizations, their members, plans, webhook endpoints and API usage
 * (docs/decisions/0010-corporate-is-an-area-on-its-own-endpoint.md).
 *
 * Every call fails for an account without the Corporate plan.
 */
final readonly class Corporate
{
    public function __construct(private GraphQLClient $client) {}

    /**
     * The child organizations, filtered by id, name or creation date.
     *
     * @return list<ChildOrganization>
     *
     * @throws AutentiqueException
     */
    public function organizations(
        ?int $id = null,
        ?string $name = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $until = null,
        int $page = 1,
        int $perPage = 60,
    ): array {
        $data = $this->send(Operation::CorporateOrganizations, [
            'id' => $id,
            'name' => $name,
            'start_date' => $from?->format('Y-m-d'),
            'end_date' => $until?->format('Y-m-d'),
            'limit' => $perPage,
            'page' => $page,
        ]);

        return array_map(ChildOrganization::fromPayload(...), $data->list(Operation::CorporateOrganizations->field()));
    }

    /**
     * @throws AutentiqueException
     */
    public function createOrganization(NewChildOrganization $organization): ChildOrganization
    {
        return $this->organization(Operation::CorporateCreateOrganization, ['organization' => $organization->toArray()]);
    }

    /**
     * @throws AutentiqueException
     */
    public function updateOrganization(int $id, ChildOrganizationChanges $changes): ChildOrganization
    {
        return $this->organization(Operation::CorporateUpdateOrganization, ['id' => $id, 'organization' => $changes->toArray()]);
    }

    /**
     * Moves a child organization to another plan, for a month unless told
     * otherwise.
     *
     * @throws AutentiqueException
     */
    public function changePlan(int $organizationId, CustomPlan $plan, ?int $daysUntilExpiration = null): ChildOrganization
    {
        return $this->organization(Operation::CorporateUpdateOrganizationPlan, [
            'organization_id' => $organizationId,
            'plan' => $plan->value,
            'days_until_expiration' => $daysUntilExpiration,
        ]);
    }

    /**
     * Deletes a child organization, which Autentique allows only once it has no
     * members.
     *
     * @throws AutentiqueException
     */
    public function deleteOrganization(int $id): bool
    {
        return $this->send(Operation::CorporateDeleteOrganization, ['id' => $id])->bool(Operation::CorporateDeleteOrganization->field());
    }

    /**
     * The plan each of these child organizations is on.
     *
     * @param  list<int>  $organizationIds
     * @return list<OrganizationPlan>
     *
     * @throws AutentiqueException
     */
    public function plans(array $organizationIds): array
    {
        $data = $this->send(Operation::CorporateOrganizationsPlans, ['organizations_ids' => array_values($organizationIds)]);

        return array_map(OrganizationPlan::fromPayload(...), $data->list(Operation::CorporateOrganizationsPlans->field()));
    }

    /**
     * What a child organization's use of the API cost, day by day.
     *
     * @throws AutentiqueException
     */
    public function apiUsage(int $organizationId, DateTimeInterface $from, DateTimeInterface $until): ApiUsage
    {
        $data = $this->send(Operation::CorporateApiUsage, [
            'organization_id' => $organizationId,
            'start_date' => $from->format('Y-m-d'),
            'end_date' => $until->format('Y-m-d'),
        ]);

        return ApiUsage::fromPayload($data->object(Operation::CorporateApiUsage->field()) ?? Payload::of([]));
    }

    /**
     * The members of a child organization, each with the API token that acts
     * as them.
     *
     * @return list<OrganizationMember>
     *
     * @throws AutentiqueException
     */
    public function members(
        int $organizationId,
        ?string $id = null,
        ?string $name = null,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $until = null,
        int $page = 1,
        int $perPage = 60,
    ): array {
        $data = $this->send(Operation::CorporateOrganizationMembers, [
            'organization_id' => $organizationId,
            'id' => $id,
            'name' => $name,
            'start_date' => $from?->format('Y-m-d'),
            'end_date' => $until?->format('Y-m-d'),
            'limit' => $perPage,
            'page' => $page,
        ]);

        return array_map(OrganizationMember::fromPayload(...), $data->list(Operation::CorporateOrganizationMembers->field()));
    }

    /**
     * @throws AutentiqueException
     */
    public function addMember(int $organizationId, NewMember $member): OrganizationMember
    {
        return $this->member(Operation::CorporateCreateMember, ['organization_id' => $organizationId, 'member' => $member->toArray()]);
    }

    /**
     * @throws AutentiqueException
     */
    public function updateMember(int $organizationId, string $userId, NewMember $member): OrganizationMember
    {
        return $this->member(Operation::CorporateUpdateMember, [
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'member' => $member->toArray(),
        ]);
    }

    /**
     * @throws AutentiqueException
     */
    public function removeMember(int $organizationId, string $userId): bool
    {
        return $this->send(Operation::CorporateDeleteMember, ['organization_id' => $organizationId, 'user_id' => $userId])
            ->bool(Operation::CorporateDeleteMember->field());
    }

    /**
     * A login token for the dashboard in an iframe, valid for five minutes.
     *
     * @throws AutentiqueException
     */
    public function loginCode(int $organizationId, string $userId): string
    {
        return $this->send(Operation::CorporateCreateLoginCode, ['organization_id' => $organizationId, 'user_id' => $userId])
            ->string(Operation::CorporateCreateLoginCode->field());
    }

    /**
     * Registers a webhook endpoint for a child organization, and returns it
     * with its secret, which Autentique shows only here.
     *
     * An endpoint listens to one kind of resource, and only that resource's
     * events reach it; two signature events Autentique documents are not
     * offered for registration.
     *
     * @param  list<WebhookEventType>  $events
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function createWebhookEndpoint(
        int $organizationId,
        string $url,
        string $name,
        WebhookEndpointType $type,
        array $events,
        WebhookFormat $format = WebhookFormat::Json,
        ?string $folderId = null,
    ): WebhookEndpoint {
        if ($events === []) {
            throw new InvalidInput('A webhook endpoint listens to at least one event.');
        }

        $names = [];

        foreach ($events as $event) {
            if ($event->resource() !== strtolower($type->value)) {
                throw new InvalidInput("An endpoint for {$type->value} events cannot listen to {$event->value}.");
            }

            $names[] = $event->endpointName() ?? throw new InvalidInput("{$event->value} cannot be registered through the API.");
        }

        $data = $this->send(Operation::CorporateCreateEndpoint, [
            'organization_id' => $organizationId,
            'url' => $url,
            'format' => $format->value,
            'type' => $type->value,
            'name' => $name,
            'events' => $names,
            'folder_id' => $folderId,
        ]);

        return WebhookEndpoint::fromPayload($data->object(Operation::CorporateCreateEndpoint->field()) ?? Payload::of([]));
    }

    /**
     * The custom plans, or the one with this id.
     *
     * @return list<CorporatePlan>
     *
     * @throws AutentiqueException
     */
    public function subscriptionPlans(?string $id = null, int $page = 1, int $perPage = 60): array
    {
        $data = $this->send(Operation::CorporateSubscriptionPlans, ['id' => $id, 'limit' => $perPage, 'page' => $page]);

        return array_map(CorporatePlan::fromPayload(...), $data->list(Operation::CorporateSubscriptionPlans->field()));
    }

    /**
     * @throws AutentiqueException
     */
    public function createSubscriptionPlan(NewSubscriptionPlan $plan): CorporatePlan
    {
        return $this->plan(Operation::CorporateCreateSubscriptionPlan, ['plan' => $plan->toArray()]);
    }

    /**
     * @throws AutentiqueException
     */
    public function updateSubscriptionPlan(string $id, NewSubscriptionPlan $plan): CorporatePlan
    {
        return $this->plan(Operation::CorporateUpdateSubscriptionPlan, ['id' => $id, 'plan' => $plan->toArray()]);
    }

    /**
     * Puts a child organization on a custom plan, renewing on `$renewAt` or at
     * the plan's next interval.
     *
     * @throws AutentiqueException
     */
    public function assignSubscriptionPlan(int $organizationId, string $planId, ?DateTimeInterface $renewAt = null): Subscription
    {
        $data = $this->send(Operation::CorporateUpdateOrganizationSubscription, [
            'organization_id' => $organizationId,
            'plan_id' => $planId,
            'renew_date' => $renewAt?->format('Y-m-d'),
        ]);

        return Subscription::fromPayload($data->object(Operation::CorporateUpdateOrganizationSubscription->field()) ?? Payload::of([]));
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function organization(Operation $operation, array $variables): ChildOrganization
    {
        return ChildOrganization::fromPayload($this->send($operation, $variables)->object($operation->field()) ?? Payload::of([]));
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function member(Operation $operation, array $variables): OrganizationMember
    {
        return OrganizationMember::fromPayload($this->send($operation, $variables)->object($operation->field()) ?? Payload::of([]));
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function plan(Operation $operation, array $variables): CorporatePlan
    {
        return CorporatePlan::fromPayload($this->send($operation, $variables)->object($operation->field()) ?? Payload::of([]));
    }

    /**
     * Sends, leaving out every variable that is null.
     *
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function send(Operation $operation, array $variables): Payload
    {
        return Payload::of($this->client->send($operation, array_filter($variables, fn(mixed $value): bool => $value !== null)));
    }
}
