# Decisions

One file per decision, numbered. The number is the identifier: it is what code
cites, so it never changes and is never reused.

Each records the context that forced a choice, the alternatives weighed, and an
outcome section written when the code ships. That last part is the point: a
decision record whose outcome is never written back is how a document drifts
away from the code it describes.

Records are written **before** the code that depends on them, in the same
change or an earlier one, so that a comment citing a record always has a record
to point at.

| # | Decision |
|---|---|
| [0001](0001-one-laravel-package.md) | One Laravel package, with no framework free core |
| [0002](0002-php-and-laravel-floor.md) | PHP 8.4.1 and Laravel 13 as the floor |
| [0003](0003-operations-live-in-graphql-files.md) | Operations live in `.graphql` files, carry variables only, and are validated against the schema |
| [0004](0004-the-transport-is-laravels-http-client.md) | The transport is Laravel's HTTP client, for JSON and for uploads alike |
| [0005](0005-exceptions-name-the-real-fault.md) | Exceptions name the fault that actually occurred |
| [0006](0006-sandbox-is-chosen-per-call.md) | Sandbox is chosen per call, with the default in the config |
| [0007](0007-webhooks-are-verified-and-idempotency-is-the-applications.md) | Webhooks are verified on the raw body, and idempotency is the application's |
| [0008](0008-responses-are-typed-value-objects.md) | Responses are typed, immutable value objects |
| [0009](0009-only-a-refused-request-is-retried.md) | Only a request Autentique refused to process is retried |

The first eight were decided before the first line of code. Each gains its
outcome when the code it decided ships.

When and why each question was put is dated in
[the decision log](../history/decision-log.md).
