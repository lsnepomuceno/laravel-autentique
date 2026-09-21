<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Testing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use LSNepomuceno\LaravelAutentique\Enums\WebhookEventType;
use LSNepomuceno\LaravelAutentique\Webhooks\SignatureVerifier;

/**
 * A webhook as Autentique would post it, signed, for an application's tests of
 * its own listeners and routes.
 *
 * ```php
 * $webhook = FakeWebhook::make(WebhookEventType::DocumentFinished, ['id' => $document->autentique_id]);
 *
 * $this->call('POST', '/webhooks/autentique', server: $webhook->server(), content: $webhook->body)
 *     ->assertOk();
 * ```
 */
final readonly class FakeWebhook
{
    private function __construct(
        public string $body,
        public string $signature,
    ) {}

    /**
     * @param  array<string, mixed>  $object  The document, signature or member the event is about.
     * @param  array<string, mixed>  $previousAttributes
     * @param  ?string  $secret  The configured `autentique.webhooks.secret` when absent.
     */
    public static function make(
        WebhookEventType $type,
        array $object = [],
        ?string $id = null,
        array $previousAttributes = [],
        #[\SensitiveParameter]
        ?string $secret = null,
    ): self {
        $body = json_encode([
            'id' => 'fake-webhook',
            'object' => 'webhook',
            'name' => 'fake endpoint',
            'format' => 'json',
            'event' => [
                'id' => $id ?? 'fake-event-' . bin2hex(random_bytes(8)),
                'object' => 'event',
                'organization' => 1,
                'type' => $type->value,
                'data' => ['object' => $type->resource(), ...$object],
                'previous_attributes' => $previousAttributes,
                'created_at' => now()->toIso8601ZuluString('microsecond'),
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $secret ??= Container::getInstance()->make(Repository::class)->get('autentique.webhooks.secret');

        return new self($body, SignatureVerifier::sign($body, is_string($secret) ? $secret : ''));
    }

    /**
     * The server variables for a test request: the signature header and the
     * content type.
     *
     * @return array<string, string>
     */
    public function server(): array
    {
        return [
            'HTTP_' . strtoupper(str_replace('-', '_', SignatureVerifier::HEADER)) => $this->signature,
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    /**
     * The same as headers, for a client that takes them.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [SignatureVerifier::HEADER => $this->signature, 'Content-Type' => 'application/json'];
    }
}
