# 0005: Exceptions name the fault that actually occurred

**Status:** decided before the code, tracked by #8.

## Context

Autentique reports failures in three different places, and only two of them
are HTTP statuses:

| What happened | How it arrives |
|---|---|
| Missing or invalid token | HTTP 401, `{"message":"unauthorized"}` |
| Rate limit exceeded (10, 60 or 200 requests per minute, by plan) | HTTP 429, `{"message":"Too Many Attempts."}` |
| Everything else | **HTTP 200**, with an `errors` array beside `data` |

Inside `errors`, the documentation shows three shapes:

- a query that does not match the schema, with a GraphQL message and locations;
- a resource that does not exist, `"Folder not found"`, with `data.folder: null`;
- a validation failure, with `message: "validation"` and the details in
  `extensions.validation`, keyed by field, each a list of codes. A code may carry
  a parameter after a colon: `must_be_at_least_characters:3`.

The documentation lists 22 codes with English and Portuguese texts
(`document_not_found`, `not_your_turn`, `unavailable_credits`, …), and mentions
others on individual pages (`too_many_resent_emails`, `signature_not_found`,
`sms_delivery_not_allowed_on_foreign_documents`).

`laravel-autentique-v2` did not check the HTTP status, read the first property
of the first error as the message, and its token check built an exception
without throwing it.

A client that treats HTTP 200 as success, or turns every failure into one
generic exception, forces every caller to parse Autentique's error format
themselves, which is the job the package exists to do.

## Decision

**Every failure becomes an exception that names what happened**, all extending
one base, `AutentiqueException`, so a caller can catch everything in one place
or one fault precisely:

| Exception | When |
|---|---|
| `Unauthenticated` | HTTP 401, or `errors[].message` of `Unauthorized` |
| `RateLimited` | HTTP 429 |
| `ValidationFailed` | `message: "validation"`, carrying the codes per field with their parameters parsed |
| `NotFound` | a `*_not_found` code, or a not found message with `null` data |
| `GraphQLError` | anything else in `errors`, carrying every error rather than the first |

**An `errors` array is a failure even when `data` is partly filled.** The
package never returns a half answer as if it were whole.

**The documented codes are an enum**, with the English and Portuguese texts as
translations, so a caller can match on a case instead of a string and show the
message in the application's locale.

**The token never appears in an exception.** Not in the message, not in a
context array, not in a dumped request.

## Consequences

- A code Autentique adds and does not document still reaches the caller, inside
  `ValidationFailed` or `GraphQLError`, as its raw string. The enum grows when
  the code is seen.
- Retrying on HTTP 429 is the transport's configured policy
  ([0004](0004-the-transport-is-laravels-http-client.md)); `RateLimited` is what
  arrives when that policy gives up.

## Alternatives rejected

| | Why not |
|---|---|
| One exception carrying the raw `errors` | Every caller parses Autentique's format again, and gets it wrong in a different way |
| Returning `data` and `errors` side by side | A caller that forgets to look at `errors` treats a failure as success, and HTTP 200 makes that the easy mistake |
| One exception class per error code | Dozens of classes, most of them never caught individually. The enum gives the precision without the classes |

## Outcome

Shipped in #8, with two additions.

**A shared parent, `RequestFailed`**, abstract, between the base and the six
exceptions a request can end in. It carries what support needs from any of
them: the operation, the HTTP status, the `X-Attq-Request-Id` Autentique
returns, and every error.

**`TransportFailed`**, for a connection that failed or an answer that was not
GraphQL at all: a 5xx, a proxy's page, a body that is not JSON. The record
listed only what Autentique reports; a client also has to name what it could
not get an answer to, and say that the request may have been processed.

The codes are `Enums\ErrorCode`, with the text in `lang/en/errors.php` and
`lang/pt_BR/errors.php`. Five codes the documentation mentions outside its table
got text written in the table's register.
