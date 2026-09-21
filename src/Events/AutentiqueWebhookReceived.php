<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Events;

use LSNepomuceno\LaravelAutentique\Data\WebhookEvent;

/**
 * A verified webhook, dispatched by the package's route.
 *
 * One event class for all seventeen types; a listener filters on `$event->type`.
 * Autentique may deliver the same event twice and in any order: a listener that
 * must not act twice keys its work on `$event->id`
 * (docs/decisions/0007-webhooks-are-verified-and-idempotency-is-the-applications.md).
 */
final readonly class AutentiqueWebhookReceived
{
    public function __construct(public WebhookEvent $event) {}
}
