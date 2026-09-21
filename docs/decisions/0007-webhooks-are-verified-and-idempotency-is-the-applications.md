# 0007: Webhooks are verified on the raw body, and idempotency is the application's

**Status:** decided before the code, tracked by #16.

## Context

Autentique posts events to an endpoint registered in its dashboard. What the
documentation says about them, and what it leaves out:

- **Seventeen event types**: four about documents (`created`, `updated`,
  `deleted`, `finished`), eleven about signatures (`created`, `updated`,
  `deleted`, `viewed`, `accepted`, `rejected`, four about biometric
  verification, `delivery_failed`) and two about members.
- **Authenticity:** the header `X-Autentique-Signature` carries the hex HMAC
  SHA-256 of the request body, keyed with the endpoint's secret. There is no
  timestamp and no prefix, so **there is no replay protection**: a captured
  request stays valid forever.
- **Delivery:** retried after 60, 120 and 300 seconds, then kept as failed for
  14 days. Order is not guaranteed and **the same event can arrive twice**.
- **Shape:** every event is `{id, type, data, previous_attributes, created_at}`,
  but the examples disagree about where the resource is. One puts it at
  `event.data.object`, the others directly at `event.data`.
- A legacy format, URL encoded with Portuguese keys, still fires for endpoints
  registered before it was deprecated; no new endpoint can use it.

## Decision

**The signature is checked on the raw body, in constant time,** by a middleware
the application can put on its own route, and on the route the package
registers when `autentique.webhooks.path` is set. A missing header, a wrong
signature or a body altered by one byte is refused with HTTP 401 before any
application code runs. The body is never decoded and re-encoded before it is
checked: a verification that depends on key order or whitespace fails on real
traffic.

**Both documented shapes are accepted.** The typed event reads the resource from
`data.object` when it is there and from `data` otherwise.

**A verified event is dispatched as a Laravel event** carrying a typed value,
with its type as an enum. The application listens as it listens to anything
else, queued or not.

**Idempotency is the application's, and the documentation says so plainly.**
The package cannot know what processing an event twice would do. It offers an
opt-in guard that remembers `event.id` in the cache for a configured time and
drops a repeat, which covers retries and duplicates but not a replay beyond that
time.

**The legacy format is out of scope.** No new endpoint can be registered with
it.

## Consequences

- An application that does not enable the guard, and whose handler is not
  idempotent, will act twice on a duplicated delivery. The guide shows the
  two ways to avoid it.
- Replay protection cannot be offered, because Autentique signs no timestamp.
  The guide says so instead of implying otherwise.
- The webhook secret is configuration, and like the token it never appears in an
  exception or a log.

## Alternatives rejected

| | Why not |
|---|---|
| Verifying a re-encoded body, as the documentation's Python example does | Correct only while Autentique keeps producing compact JSON with the same key order |
| Deduplication always on | Needs a cache store the application may not have configured, and hides the problem from the one reading the handler |
| One Laravel event class per event type | Seventeen classes to listen to, when one event with an enum is filtered in one line |

## Outcome

Shipped in #16 as decided: `Webhooks\SignatureVerifier` on the raw body with
`hash_equals()`, `Webhooks\VerifyAutentiqueSignature` as the middleware,
`Data\WebhookEvent` reading both shapes, `Events\AutentiqueWebhookReceived`
dispatched by the route, and the guard keyed on `event.id` through the cache,
off unless `autentique.webhooks.deduplicate` names a number of seconds.

Three details the record did not settle:

- **A missing secret throws**, `MissingWebhookSecret`, rather than answering
  401. A misconfiguration answered with 401 looks, from Autentique's side and
  from the logs, exactly like an attack being refused, and nobody looks.
- **The resource stays an array**, read through `payload()`. Webhook payloads
  are shaped differently from the API's answers and differ between event types,
  so typing them would have been a second, drifting copy of guesses.
- **A signed body that is not an event answers 400**, so a misrouted request is
  distinguishable from a forged one.

**Checked against real deliveries on 2026-09-21.** An endpoint registered in the
dashboard received four events from a sandbox document; every one verified with
the endpoint's secret, went through the middleware and the controller, and was
dispatched with the right type, document id and previous attributes, while the
same body with one byte changed answered 401. The live API sends the flat shape,
the resource at `event.data`, which settles which of the documentation's two
examples is current; both are still read. One delivery is kept, with its
personal data replaced, as `tests/Resources/responses/webhooks/live-document-finished.json`.
