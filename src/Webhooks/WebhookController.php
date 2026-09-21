<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Webhooks;

use Illuminate\Contracts\Cache\Factory as Caches;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\{JsonResponse, Request};
use LSNepomuceno\LaravelAutentique\Data\WebhookEvent;
use LSNepomuceno\LaravelAutentique\Events\AutentiqueWebhookReceived;
use LSNepomuceno\LaravelAutentique\Exceptions\UnexpectedResponse;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The route the package registers for webhooks, behind
 * `VerifyAutentiqueSignature`.
 *
 * It answers quickly, as Autentique asks: the work belongs in the
 * application's listeners, queued if it is slow. Autentique retries a failed
 * delivery after 60, 120 and 300 seconds.
 */
final readonly class WebhookController
{
    public function __construct(
        private Dispatcher $events,
        private Repository $config,
        private Caches $caches,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $event = WebhookEvent::fromPayload(Payload::of(json_decode($request->getContent(), true)));
        } catch (UnexpectedResponse) {
            return new JsonResponse(['message' => 'Not an Autentique event.'], 400);
        }

        if ($this->isRepeat($event)) {
            return new JsonResponse(['received' => true, 'duplicate' => true]);
        }

        $this->events->dispatch(new AutentiqueWebhookReceived($event));

        return new JsonResponse(['received' => true]);
    }

    /**
     * The opt-in guard against a repeated delivery: remembers each event id for
     * `autentique.webhooks.deduplicate` seconds, and drops one seen within them.
     */
    private function isRepeat(WebhookEvent $event): bool
    {
        $seconds = $this->config->get('autentique.webhooks.deduplicate');

        if (! is_numeric($seconds) || (int) $seconds < 1) {
            return false;
        }

        $store = $this->config->get('autentique.webhooks.cache_store');

        return ! $this->caches
            ->store(is_string($store) ? $store : null)
            ->add("autentique:webhook:{$event->id}", true, (int) $seconds);
    }
}
