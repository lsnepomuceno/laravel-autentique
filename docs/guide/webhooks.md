# Webhooks

Autentique posts an event to your application whenever something happens to a
document, a signature or a member of the organization. The package verifies
each one and hands it to you as a Laravel event.

## Setting it up

1. Register an endpoint in Autentique's dashboard, pointing at
   `https://your-app.example/webhooks/autentique`, and choose its events.
   Copy the secret it shows.
2. Configure the package:

   ```ini
   AUTENTIQUE_WEBHOOK_SECRET=the-secret-from-the-dashboard
   AUTENTIQUE_WEBHOOK_PATH=webhooks/autentique
   ```

3. Listen:

   ```php
   use LSNepomuceno\LaravelAutentique\Enums\WebhookEventType;
   use LSNepomuceno\LaravelAutentique\Events\AutentiqueWebhookReceived;

   Event::listen(function (AutentiqueWebhookReceived $received) {
       $event = $received->event;

       if ($event->type === WebhookEventType::DocumentFinished) {
           Contract::where('autentique_id', $event->documentId())->update(['signed' => true]);
       }
   });
   ```

With a path configured, the package registers `POST webhooks/autentique`, named
`autentique.webhook`, **outside the `web` middleware group**: Autentique sends no
CSRF token, and the signature is what authenticates the request. Anything else
the route needs goes in `autentique.webhooks.middleware`.

Without a path, no route is registered. Put the middleware on your own:

```php
use LSNepomuceno\LaravelAutentique\Webhooks\VerifyAutentiqueSignature;

Route::post('hooks/signatures', SignatureHookController::class)->middleware(VerifyAutentiqueSignature::class);
```

## Verification

`X-Autentique-Signature` carries the hex HMAC SHA-256 of the request's raw body,
keyed with the endpoint's secret. The middleware computes it over the body
exactly as it arrived, compares in constant time, and answers **401** before any
of your code runs when it does not match. The body is never decoded and
re-encoded first, because a check that depends on key order or whitespace fails
on real traffic ([0007](/decisions/0007-webhooks-are-verified-and-idempotency-is-the-applications)).

With no secret configured, a webhook throws `MissingWebhookSecret` instead of
being answered, so it reaches your error tracker rather than passing as a quiet
stream of 401s.

## The event

`$received->event` is a `Data\WebhookEvent`:

| Property | |
|---|---|
| `$id` | unique per event and the same on every delivery of it: what to deduplicate on |
| `$type` | a `WebhookEventType`, or null for a type the package does not know yet |
| `$rawType` | the type as it arrived, `signature.accepted` |
| `$object` | the document, signature or member, as the array Autentique sent |
| `$previousAttributes` | on `*.updated`, the values that changed, as they were |
| `$organizationId`, `$createdAt`, `$endpoint` | |

`documentId()` and `publicId()` read the ids a listener usually needs, and
`payload()` reads any other field, typed:

```php
$event->payload()->date('signed');               // CarbonImmutable
$event->payload()->nullableString('user.email');
```

The resource is kept as Autentique sent it because webhook payloads are shaped
differently from API answers: an action is `"Sign"` rather than `SIGN`, events are
timestamps. Autentique's own examples also disagree about where the resource
sits, `event.data.object` in one and `event.data` in the others; both are read,
and `$object` holds it either way.

## The seventeen types

| Resource | Types |
|---|---|
| Document | `created`, `updated`, `deleted`, `finished` |
| Signature | `created`, `updated`, `deleted`, `viewed`, `accepted`, `rejected`, `biometric_approved`, `biometric_unapproved`, `biometric_reset`, `biometric_rejected`, `delivery_failed` |
| Member | `created`, `deleted` |

`$event->type->resource()` says which of the three an event is about.

## Duplicates, order and replays

Autentique says plainly that **the same event can arrive twice, and events can
arrive in any order**. A failed delivery is retried after 60, 120 and 300
seconds. The package cannot know what acting twice would do in your
application, so idempotency is yours. There are two ways:

- **Key your work on `$event->id`**, the robust way: an `updateOrCreate`, a
  unique column, a job with a unique id.
- **Turn on the package's guard**, which remembers each id in the cache and drops
  a repeat:

  ```ini
  AUTENTIQUE_WEBHOOK_DEDUPLICATE=900      # seconds; longer than Autentique's retries
  ```

  A repeat is answered `200 {"received": true, "duplicate": true}` and not
  dispatched. It needs a cache store shared by every server that receives
  webhooks; `AUTENTIQUE_WEBHOOK_CACHE_STORE` names one.

**There is no replay protection**, and no client can add any: Autentique signs
no timestamp, so a captured request stays valid. The guard only covers a replay
within its window. Treat the endpoint accordingly: HTTPS only, and never act on
a webhook in a way you would not accept twice.

## Answer fast

Autentique expects a quick 2xx. Anything slow belongs in a queued listener:

```php
final class RecordSignature implements ShouldQueue
{
    public function handle(AutentiqueWebhookReceived $received): void
    {
        // …
    }
}
```

## Legacy webhooks

Autentique's old per-account webhook, URL encoded with Portuguese keys, still
fires for endpoints registered before it was deprecated. No new endpoint can use
it, and the package does not read it.
