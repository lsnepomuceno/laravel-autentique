<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Event, Route};
use LSNepomuceno\LaravelAutentique\Data\WebhookEvent;
use LSNepomuceno\LaravelAutentique\Enums\WebhookEventType;
use LSNepomuceno\LaravelAutentique\Events\AutentiqueWebhookReceived;
use LSNepomuceno\LaravelAutentique\Exceptions\MissingWebhookSecret;
use LSNepomuceno\LaravelAutentique\Support\Payload;
use LSNepomuceno\LaravelAutentique\Webhooks\SignatureVerifier;

/**
 * A webhook fixture, as the raw body Autentique would send.
 */
function webhookBody(string $name): string
{
    return (string) file_get_contents(packageRoot() . "/tests/Resources/responses/webhooks/{$name}.json");
}

describe('the signature', function () {
    it('accepts the HMAC of the raw body', function () {
        $body = '{"a":1}';

        expect(SignatureVerifier::verify($body, hash_hmac('sha256', $body, 'secret'), 'secret'))->toBeTrue()
            ->and(SignatureVerifier::verify($body, strtoupper(hash_hmac('sha256', $body, 'secret')), 'secret'))->toBeTrue();
    });

    it('refuses anything else', function (string $body, ?string $signature, string $secret) {
        expect(SignatureVerifier::verify($body, $signature, $secret))->toBeFalse();
    })->with([
        'no header' => ['{"a":1}', null, 'secret'],
        'an empty header' => ['{"a":1}', '', 'secret'],
        'another secret' => ['{"a":1}', hash_hmac('sha256', '{"a":1}', 'other'), 'secret'],
        'one byte changed' => ['{"a":2}', hash_hmac('sha256', '{"a":1}', 'secret'), 'secret'],
        'the same JSON re-encoded' => ['{"a": 1}', hash_hmac('sha256', '{"a":1}', 'secret'), 'secret'],
        'no secret' => ['{"a":1}', hash_hmac('sha256', '{"a":1}', ''), ''],
    ]);
});

describe('the route', function () {
    beforeEach(fn() => Event::fake([AutentiqueWebhookReceived::class]));

    it('dispatches a verified event, from the shape with data.object', function () {
        $this->postWebhook(webhookBody('document-updated'))->assertOk()->assertJson(['received' => true]);

        Event::assertDispatched(function (AutentiqueWebhookReceived $received): bool {
            $event = $received->event;

            return $event->id === '21fbcec9-e1b5-4dca-afbb-022061e9ea88'
                && $event->type === WebhookEventType::DocumentUpdated
                && $event->organizationId === 1
                && $event->object['name'] === 'Updated name'
                && $event->previousAttributes['name'] === 'teste'
                && $event->documentId() === '89c7d2ab31f9f5a13b3d20ecf53319af387e54d240ae7be993'
                && $event->publicId() === null
                && $event->endpoint === 'test endpoint 2'
                && $event->createdAt?->toDateTimeString() === '2024-08-26 18:03:27';
        });
    });

    it('dispatches a verified event, from the shape with the resource at data', function () {
        $this->postWebhook(webhookBody('signature-accepted'))->assertOk();

        Event::assertDispatched(function (AutentiqueWebhookReceived $received): bool {
            $event = $received->event;

            return $event->type === WebhookEventType::SignatureAccepted
                && $event->publicId() === 'f8911dcd-dfcd-11ef-9465-42010a2b610e'
                && $event->documentId() === 'f48a8b465d02dd87559e08f06c41e3b6d548c4d7ad835eb0f'
                && $event->payload()->date('signed')?->toDateTimeString() === '2025-01-31 12:22:30'
                && $event->previousAttributes === [];
        });
    });

    it('reads a delivery recorded from the live API, personal data replaced', function () {
        // Recorded on 2026-09-21 from a real document.finished delivery: the
        // resource directly at event.data, with `object` a type marker rather
        // than the resource, and previous_attributes beside data.
        $this->postWebhook(webhookBody('live-document-finished'))->assertOk();

        Event::assertDispatched(function (AutentiqueWebhookReceived $received): bool {
            $event = $received->event;

            return $event->type === WebhookEventType::DocumentFinished
                && $event->documentId() === 'da0454a42b8859527151f6cd08430a40850cc873e35b52714'
                && $event->previousAttributes === ['signed_count' => 1]
                && $event->endpoint === 'laravel-autentique e2e'
                && $event->payload()->nullableBool('sandbox') === true
                && $event->payload()->nullableString('signatures.0.action') === 'Sign'
                && $event->payload()->date('signatures.0.signed')?->toIso8601ZuluString() === '2026-09-21T18:09:30Z';
        });
    });

    it('dispatches a member event', function () {
        $this->postWebhook(webhookBody('member-created'))->assertOk();

        Event::assertDispatched(fn(AutentiqueWebhookReceived $received): bool => $received->event->type === WebhookEventType::MemberCreated
            && $received->event->documentId() === null
            && $received->event->payload()->nullableString('group.name') === 'Administrador');
    });

    it('refuses a wrong signature before anything runs', function () {
        $this->postWebhook(webhookBody('signature-accepted'), 'deadbeef')->assertUnauthorized()->assertJson(['message' => 'Invalid signature.']);

        Event::assertNotDispatched(AutentiqueWebhookReceived::class);
    });

    it('refuses a body altered after signing', function () {
        $body = webhookBody('signature-accepted');
        $signature = SignatureVerifier::sign($body, 'the-webhook-secret');

        $this->postWebhook(str_replace('Felipe', 'Mallory', $body), $signature)->assertUnauthorized();

        Event::assertNotDispatched(AutentiqueWebhookReceived::class);
    });

    it('fails loudly when no secret is configured', function () {
        config()->set('autentique.webhooks.secret', null);
        $this->withoutExceptionHandling();

        $this->postWebhook('{}', 'anything');
    })->throws(MissingWebhookSecret::class);

    it('answers 400 to a signed body that is not an event', function () {
        $this->postWebhook('{"hello":"world"}')->assertStatus(400);

        Event::assertNotDispatched(AutentiqueWebhookReceived::class);
    });

    it('dispatches a duplicate delivery again, unless deduplication is on', function () {
        $this->postWebhook(webhookBody('signature-accepted'))->assertOk();
        $this->postWebhook(webhookBody('signature-accepted'))->assertOk();

        Event::assertDispatchedTimes(AutentiqueWebhookReceived::class, 2);
    });

    it('drops a duplicate delivery when deduplication is on', function () {
        config()->set('autentique.webhooks.deduplicate', 600);

        $this->postWebhook(webhookBody('signature-accepted'))->assertOk()->assertJsonMissing(['duplicate' => true]);
        $this->postWebhook(webhookBody('signature-accepted'))->assertOk()->assertJson(['duplicate' => true]);
        $this->postWebhook(webhookBody('document-updated'))->assertOk()->assertJsonMissing(['duplicate' => true]);

        Event::assertDispatchedTimes(AutentiqueWebhookReceived::class, 2);
    });

    it('is registered outside the web group, so no CSRF token is asked for', function () {
        $route = Route::getRoutes()->getByName('autentique.webhook');

        expect($route?->uri())->toBe('webhooks/autentique')
            ->and($route?->gatherMiddleware())->not->toContain('web');
    });
});

it('reads every event type Autentique documents', function (WebhookEventType $type) {
    $event = WebhookEvent::fromPayload(Payload::of(['event' => ['id' => 'e1', 'type' => $type->value, 'data' => ['id' => 'x']]]));

    expect($event->type)->toBe($type)
        ->and($type->resource())->toBeIn(['document', 'signature', 'member']);
})->with(WebhookEventType::cases());

it('keeps a type it does not know yet, as it arrived', function () {
    $event = WebhookEvent::fromPayload(Payload::of(['event' => ['id' => 'e1', 'type' => 'invoice.paid', 'data' => []]]));

    expect($event->type)->toBeNull()
        ->and($event->rawType)->toBe('invoice.paid');
});
