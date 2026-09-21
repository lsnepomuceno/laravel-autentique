<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Enums\ChildOrganizationPlan;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * An organization a Corporate account created and manages.
 */
final readonly class ChildOrganization
{
    /**
     * @param  list<int>  $defaultEmailTemplateIds
     * @param  list<Group>  $groups
     */
    public function __construct(
        public int $id,
        public ?string $uuid,
        public ?string $name,
        public ?string $cnpj,
        public ?ChildOrganizationPlan $plan,
        public array $defaultEmailTemplateIds,
        /** The deprecated per organization webhook, when one is set. */
        public ?string $webhookUrl,
        public array $groups,
        public ?CarbonImmutable $createdAt,
        public ?Subscription $subscription,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $templates = [];

        foreach ($payload->object('default_email_templates_ids')?->all() ?? [] as $id) {
            if (is_numeric($id)) {
                $templates[] = (int) $id;
            }
        }

        $subscription = $payload->object('subscription');

        return new self(
            id: $payload->int('id'),
            uuid: $payload->nullableString('uuid'),
            name: $payload->nullableString('name'),
            cnpj: $payload->nullableString('cnpj'),
            plan: $payload->enum('plan', ChildOrganizationPlan::class),
            defaultEmailTemplateIds: $templates,
            webhookUrl: $payload->nullableString('settings.webhook_url'),
            groups: array_map(Group::fromPayload(...), $payload->list('groups')),
            createdAt: $payload->date('created_at'),
            subscription: $subscription === null ? null : Subscription::fromPayload($subscription),
        );
    }
}
