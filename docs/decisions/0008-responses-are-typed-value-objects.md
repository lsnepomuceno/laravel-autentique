# 0008: Responses are typed, immutable value objects

**Status:** decided before the code, tracked by #10 to #16.

## Context

`laravel-autentique-v2` returned `Illuminate\Support\Fluent` wrapping the
decoded `stdClass`, so a caller wrote `$response->data->createFolder->id`. The
caller had to know the GraphQL field names, the nesting, which fields may be
null and what format each date is in, and neither an IDE nor static analysis
could help.

The API makes this worse than it looks:

- dates arrive as ISO 8601 strings (`2023-10-17T16:43:13.000000Z`), while some
  inputs take `dd/mm/yyyy` (`ExpirationInput.notify_at`);
- ids are of several kinds: documents are 50 character hex strings, signatures
  have a `public_id`, organizations and groups are integers, folders are UUIDs;
- enum values come back in more than one form, for example `action { name }` in
  the API and `"action": "Sign"` in a webhook.

## Decision

**What an operation returns is a `final readonly` value object with typed
properties**, built from the fields the operation's `.graphql` file selects
([0003](0003-operations-live-in-graphql-files.md)):

- dates are `CarbonImmutable`;
- closed sets of values are enums;
- nested objects are value objects in turn, and lists are typed lists;
- a field the API may omit is nullable, and one it always sends is not;
- a paginated listing returns a page object carrying the items and the paging
  information.

**Inputs are value objects too,** built fluently and validated when they are
built, so a rule the API documents (a reminder forces a refusable document, one
biometric verification per signer, …) fails in the caller's code before a
request is spent.

**No response keeps the raw array around.** What the package does not model is
reached through the escape hatch, which returns the decoded `data`.

## Consequences

- A caller gets completion and static analysis on every field, and a mistyped
  field is a PHPStan error, not a runtime `null`.
- Adding a field to an operation means adding it to the value object, so the
  file and the class move together. The guide to adding an operation says so.
- The value objects are the public API, so a field removed from one is a major
  release.

## Alternatives rejected

| | Why not |
|---|---|
| `Fluent` over the decoded array, as in v2 | No types, no help from the IDE or PHPStan, and the caller learns the GraphQL shape |
| Arrays with PHPStan shapes | Typed for the analyser, untyped at runtime, and still no dates or enums |
| Keeping the raw array beside the typed properties | Two ways to read the same answer, and the untyped one is always the shortest to type |

## Outcome

Not yet written: this section is filled in when the code that depends on this
record ships.
