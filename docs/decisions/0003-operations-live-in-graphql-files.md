# 0003: Operations live in `.graphql` files, carry variables only, and are validated against the schema

**Status:** decided before the code, tracked by #7 and #9.

## Context

Every call this package makes is a GraphQL document sent to Autentique. Where
those documents live decides how they are read, reviewed, changed and checked.

`laravel-autentique-v2` kept them in `.graphql` files, one per method, which was
the right instinct. How it used them was not:

- **Values were pasted into the text.** `MakeQuery` replaced placeholders that
  looked like PHP variables with `str_replace`, so a document id went into the
  query as `document(id: "$documentId")`. That is GraphQL injection: a value
  containing a quote changes the operation.
- **A misnamed placeholder failed silently.** `changeFolder` replaced
  `$destinationFolderId`, the file said `$folderId`, and the literal string
  `$folderId` was sent to the API. Nothing could catch it, because the text is
  only a string until the server reads it.
- **The file was found by reflection** on `__METHOD__`, so renaming a method
  broke its query at runtime.

And the Autentique documentation has no schema reference, no enum reference and
no changelog. A field can disappear from the API with nothing telling the
package, until a consumer's request fails.

## Decision

**One operation per `.graphql` file, under `src/Resources/graphql/`,** split
into `queries/`, `mutations/` and `fragments/`. The operation name is the file
name.

**Values travel only as GraphQL variables.** The text of an operation is never
built, concatenated or interpolated at runtime. What reaches the server is the
file as written plus a `variables` object.

**An enum names every operation.** A string backed `Operation` enum has one case
per file, and the case knows whether it is a query or a mutation and which
endpoint it targets. Code asks for `Operation::CreateDocument`, not for a path
or a method name.

**Fragments are shared, and appended automatically.** A selection used in
several places (a document's fields, a signature's) is written once as a
fragment. The loader finds every `...Name` spread in an operation and appends
the fragments it needs, recursively and each at most once. The loaded text is
memoised for the process.

**Every file is validated against the real schema.** The schema is introspected
with a token, committed as SDL under `tests/Resources/`, and a test parses and
validates every operation against it with `webonyx/graphql-php`, which is a
development dependency only and never ships. The enums are compared with the
schema's enums the same way.

**There is an escape hatch.** `Autentique::query($graphql, $variables)` sends an
operation the package does not ship, with variables, for a selection the
package does not offer.

## Consequences

- An operation reads as GraphQL, is diffed as GraphQL, and is completed and
  checked by an IDE's GraphQL support against the same committed schema.
- A field Autentique removes, or an argument it renames, fails this package's
  suite when the schema is refreshed, rather than failing a consumer's request.
- The fields each operation selects are fixed by the package. Asking for more is
  a change to a file; asking for something else is the escape hatch.
- Reading the files costs one filesystem read per operation per process.
- Refreshing the schema needs a valid token, so it is a deliberate step run by
  the maintainer, not something CI does.

## Alternatives rejected

| | Why not |
|---|---|
| Heredoc strings in the PHP classes | Cannot be validated by a tool without extracting them first, and invite interpolation, which is the defect this record exists to prevent |
| A query builder in PHP | A second language for the same thing, larger than the operations it builds, and still has to be validated against the schema |
| Code generation from the schema | Generates a client for every type in a schema that is partly undocumented and partly Corporate only. The package's surface should be chosen, not dumped |
| Keeping v2's placeholders | Injection, and silent failure on a misnamed placeholder |

## Outcome

Not yet written: this section is filled in when the code that depends on this
record ships.
