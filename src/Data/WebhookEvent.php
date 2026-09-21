<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Data;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\Enums\WebhookEventType;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * One event Autentique posted to a webhook endpoint.
 *
 * **Autentique's own examples disagree about the shape**: one puts the resource
 * at `event.data.object`, with `previous_attributes` inside `data`; the others
 * put it at `event.data`, with `previous_attributes` beside it. Both are read,
 * and the resource ends up in `$object` either way
 * (docs/decisions/0007-webhooks-are-verified-and-idempotency-is-the-applications.md).
 *
 * The resource is kept as the array Autentique sent, because webhook payloads
 * are shaped differently from GraphQL answers (an action is `"Sign"`, events
 * are timestamps) and differ between event types.
 */
final readonly class WebhookEvent
{
    /**
     * @param  array<array-key, mixed>  $object  The document, signature or member the event is about.
     * @param  array<array-key, mixed>  $previousAttributes  On `*.updated`, the values that changed, as they were.
     */
    public function __construct(
        /** Unique per event, and the same on every delivery of it: what to deduplicate on. */
        public string $id,
        public string $rawType,
        /** Null for a type this package does not know yet. */
        public ?WebhookEventType $type,
        public ?int $organizationId,
        public ?CarbonImmutable $createdAt,
        public array $object,
        public array $previousAttributes,
        /** The endpoint's name, as registered in the dashboard. */
        public ?string $endpoint,
    ) {}

    /**
     * @throws UnexpectedResponse
     */
    public static function fromPayload(Payload $payload): self
    {
        $event = $payload->object('event') ?? Payload::of([]);
        $data = $event->object('data') ?? Payload::of([]);
        $nested = $data->object('object');

        $previous = $nested === null ? $event->object('previous_attributes') : $data->object('previous_attributes');

        $type = $event->string('type');

        return new self(
            id: $event->string('id'),
            rawType: $type,
            type: WebhookEventType::tryFrom($type),
            organizationId: $event->nullableInt('organization'),
            createdAt: $event->date('created_at'),
            object: ($nested ?? $data)->all(),
            previousAttributes: $previous?->all() ?? [],
            endpoint: $payload->nullableString('name'),
        );
    }

    /**
     * The resource, read one typed field at a time.
     */
    public function payload(): Payload
    {
        return new Payload($this->object);
    }

    /**
     * The document the event is about: its own id on a document event, the
     * document a signature belongs to on a signature event.
     */
    public function documentId(): ?string
    {
        return match ($this->type?->resource()) {
            'document' => $this->payload()->nullableString('id'),
            'signature' => $this->payload()->nullableString('document'),
            default => null,
        };
    }

    /**
     * The signature a signature event is about.
     */
    public function publicId(): ?string
    {
        return $this->type?->resource() === 'signature' ? $this->payload()->nullableString('public_id') : null;
    }
}
