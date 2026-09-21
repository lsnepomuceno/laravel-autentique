# Security policy

## Supported versions

| Version | Supported |
|---|---|
| 1.x, from `1.0.0-rc.1` | yes |

The latest minor of the current major receives security fixes.

`lsnepomuceno/laravel-autentique-v2`, the package this one replaces, is not
supported.

## Reporting a vulnerability

**Please do not open a public issue.** Use GitHub's private reporting, under
[Security → Report a vulnerability](https://github.com/lsnepomuceno/laravel-autentique/security/advisories/new),
which reaches the maintainer without disclosing anything.

Include the version, what you did, and what you expected instead. **Never
include a real API token or a webhook secret**, even an expired one: describe
where it appeared instead.

You should get an acknowledgement within a few days. If a fix is warranted it
ships as a patch release with an advisory, and you are credited unless you would
rather not be.

## What counts

Anything that lets a third party act on an Autentique account through this
package, or read what it should not. Some specific shapes, because they are the
ones worth thinking about here:

- the API token, a webhook secret, an OAuth token or a Corporate member's token
  reaching an exception message, a log line, a stack trace or any output that
  outlives the request;
- an OAuth callback accepted with a `state` that is not the one the session
  sent;
- a webhook request accepted without a valid `X-Autentique-Signature`, or a
  signature compared in a way that leaks timing;
- a value supplied by a caller changing the text of a GraphQL operation rather
  than travelling as a variable;
- a request reaching a host other than the configured endpoint.

## What does not

Some behaviour looks like a weakness and is a documented decision. Reporting one
is welcome as an issue, and it will be closed with a link rather than an
advisory:

- **A webhook can be replayed.** Autentique signs the body with no timestamp, so
  a captured request stays valid, and no client can tell a replay from a retry.
  The package offers an opt-in guard against repeated event ids
  ([0007](docs/decisions/0007-webhooks-are-verified-and-idempotency-is-the-applications.md)).
- **Sandbox is off unless configured.** A deployment with no configuration
  creates real, billed documents
  ([0006](docs/decisions/0006-sandbox-is-chosen-per-call.md)).
- **The escape hatch sends what it is given.** `Autentique::query()` exists to
  send an operation the package does not ship. It still sends values as
  variables, but the operation's text is the caller's.

## Handling of secrets

Every token, secret and password argument carries `#[\SensitiveParameter]`,
which `tests/Project/ArchTest.php` enforces, so PHP leaves the value out of every
stack trace. Network access happens only through the package's GraphQL client,
on Laravel's HTTP client, so an application's own middleware, proxy and logging
policy apply to it
([invariants](docs/spec/invariants.md)).
