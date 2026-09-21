<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * A webhook endpoint created for a child organization.
 *
 * **`$secret` is returned once, here.** It is what the endpoint's webhooks are
 * signed with, so it goes where `autentique.webhooks.secret` goes
 * (docs/decisions/0010-corporate-is-an-area-on-its-own-endpoint.md).
 */
final readonly class WebhookEndpoint
{
    /**
     * @param  list<string>  $events  As the Corporate endpoint names them, `SIGNATURE_ACCEPTED`.
     */
    public function __construct(
        public ?string $id,
        public ?string $url,
        public ?bool $active,
        public array $events,
        #[\SensitiveParameter]
        public ?string $secret,
    ) {}

    public static function fromPayload(Payload $payload): self
    {
        return new self(
            id: $payload->nullableString('webhook_endpoint.id'),
            url: $payload->nullableString('webhook_endpoint.url'),
            active: $payload->nullableBool('webhook_endpoint.active'),
            events: $payload->strings('webhook_endpoint.events'),
            secret: $payload->nullableString('secret'),
        );
    }
}
