# 0009: Only a request Autentique refused to process is retried

**Status:** decided with #8, before the code.

## Context

The transport retries, because Laravel's HTTP client makes it one line and a
rate limit is part of normal operation: 10, 60 or 200 requests per minute per
token, by plan, answered with HTTP 429 `{"message":"Too Many Attempts."}`.

But most operations here are mutations, and several of them cost money or send
messages:

| Operation | What a duplicate costs |
|---|---|
| `createDocument` | a second document, billed, with a second round of signature requests sent to every signer by email, SMS or WhatsApp |
| `createSigner` | a second signer on the document, and a second request sent |
| `resendSignatures` | another message to every signer |
| `transferDocument`, `moveDocumentToFolder` | usually harmless, but not known to be |

A request that timed out, or that received a 5xx, **may have been processed**.
Autentique documents no idempotency key, so a retry after either of those can
duplicate the operation, and the caller has no way to find out.

A 429 is different: the request was refused before anything happened.

## Decision

**Only HTTP 429 is retried**, for every operation, query or mutation, up to
`autentique.retry.times` times. The wait is the `Retry-After` header when
Autentique sends one, and `autentique.retry.sleep` milliseconds times the
attempt number otherwise.

**A timeout, a refused connection and a 5xx are never retried.** They become
`TransportFailed`, and the caller decides, knowing what the operation was.

When the retries are exhausted the caller receives `RateLimited`, carrying the
wait Autentique asked for, so a queued job can release itself for that long.

## Consequences

- A caller that wants to retry a query after a timeout does it, in its own
  code, where it is visible that the operation is safe to repeat.
- A queued job that sends documents should not add its own blanket retry on
  `TransportFailed` either, for the same reason. The guide says so.
- `retry.times` set to 0 turns retrying off entirely.

## Alternatives rejected

| | Why not |
|---|---|
| Retry every failure, as `Http::retry()` does by default | duplicates billed documents and messages after a timeout |
| Retry queries on any failure, mutations only on 429 | a second rule for a case the caller can already handle, and one more thing to explain |
| No retries at all | every caller writes the same 429 loop, and most write it without honouring `Retry-After` |

## Outcome

Shipped in #8 as decided, on `PendingRequest::retry()` with a `when` callback
that accepts only a 429 and a sleep callback that reads `Retry-After`. A
connection failure never reaches the retry at all: the callback refuses it, and
the client turns it into `TransportFailed`.
