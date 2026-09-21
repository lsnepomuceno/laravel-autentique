# 0001: One Laravel package, with no framework free core

**Status:** decided before the code, tracked by #2.

## Context

The two packages this one sits beside are split in two.
[`signet-pdf`](https://github.com/lsnepomuceno/signet-pdf) is a framework free
engine, and [`laravel-a1-pdf-sign`](https://github.com/lsnepomuceno/laravel-a1-pdf-sign)
is the Laravel adapter over it. The split is justified there by a measurement:
around 14,700 lines of engine that never touch the framework, against a few
hundred lines of wiring
([laravel-a1-pdf-sign 0039](https://github.com/lsnepomuceno/laravel-a1-pdf-sign/blob/main/docs/decisions/0039-the-core-lives-in-signet-pdf.md)).

The question is whether the same shape is right here, for the sake of symmetry.

What this package does is send GraphQL operations over HTTP and turn the answers
into typed values. Very little of that is independent of the transport, and
the part that is (the value objects, the enums, the operation files) is small.

What makes the package worth installing is precisely what Laravel provides:

| Laravel piece | What it buys |
|---|---|
| `Illuminate\Http\Client\Factory` | `Http::fake()` and `preventStrayRequests()` reach every call, retries and timeouts are configured, the host's middleware and logging see the traffic |
| `UploadedFile`, `Storage` disks | a document received in a request, or stored on S3, is uploaded without being copied to a local file first |
| the container, the facade, the config file | the ergonomics a Laravel application expects |
| events, middleware, routes | webhooks arrive as a Laravel event behind a verifying middleware |

## Decision

**One package, `lsnepomuceno/laravel-autentique`, which requires `illuminate/*`
outright.** There is no separate core and no transport contract with a PSR-18
implementation beside the Laravel one.

## Consequences

- The package cannot be used outside Laravel. The Autentique documentation
  lists community PHP clients for that case, and this package does not compete
  with them.
- The rule "reach for the framework before writing a helper" applies to the
  whole tree, with no namespace where Laravel is forbidden, unlike signet-pdf's
  byte-level code.
- Should a framework free client ever be wanted, the value objects, enums and
  `.graphql` files are what would move. Nothing here is designed to make that
  move cheaper in advance, because that is a cost paid now for a need nobody
  has.

## Alternatives rejected

| | Why not |
|---|---|
| A framework free core plus an adapter, as signet-pdf and laravel-a1-pdf-sign | Two repositories, two releases for every fix, for a core that is mostly a thin HTTP call. The split pays for itself when the engine is large and framework free; neither is true here |
| One package with a transport contract and a PSR-18 default | A second code path that nobody in a Laravel application runs, which is the one that breaks. It also invites binding a transport that `Http::fake()` does not reach |

## Outcome

Held through `1.0.0-rc.1`. The package requires `illuminate/*` outright, and
leaned on the framework everywhere the record said it would: `Http::fake()`
reaching every request including OAuth's, `UploadedFile` and `Storage` disks
streamed as uploads, a Laravel event for webhooks, the cache for deduplication,
translations for error codes, and the container for the fake.

Nothing had to be written to work around the framework, which is the
measurement that answers the question the record put.
