# From laravel-autentique-v2

What the previous package was, what was wrong with it, and what this one keeps.
Frozen: written on 2026-09-21, from a full reading of its source, before any
code of this package existed.

## What it was

[`lsnepomuceno/laravel-autentique-v2`](https://github.com/lsnepomuceno/laravel-autentique-v2),
ten commits, the last on 2021-09-06. About 1,100 lines.

| | |
|---|---|
| Runtime | PHP `^7.4 \|\| ^8.0`, Laravel 8 only, `minimum-stability: dev` |
| Namespace | `LSNepomuceno\LaravelAutentiqueV2` |
| Configuration | one key, `services.autentique.token`, added by hand to `config/services.php`; no config file |
| Endpoint | hard coded, `https://api.autentique.com.br/v2/graphql` |
| Classes | `Autentique` (transport), `Document`, `Folder`, `Signers` (builder), `MakeQuery` (loader), three exceptions |
| Operations | `.graphql` files under `src/GraphQLQueries/{documents,folders}/` |

It was never finished: there was no service provider, no facade, no README, no
licence file, and its one document test could not run.

## What it covered

Eleven operations. Documents: `createDocument`, `document`, `documents`,
`deleteDocument`, `signDocument`, `moveDocumentToFolder`. Folders:
`createFolder`, `folder`, `folders`, `deleteFolder`, `documentsByFolder`.

Nothing about signers after creation (adding, removing, resending, signing
links, biometric approval), sharing folders, organizations, email templates or
webhooks.

## What was wrong

**Values were pasted into the query text.** `MakeQuery::makeQuery()` replaced
placeholders that looked like PHP variables with `str_replace`, so an id reached
the API as `document(id: "…")` written into the operation. That is GraphQL
injection. The same mechanism broke `changeFolder` outright: the method replaced
`$destinationFolderId`, the file said `$folderId`, and the literal `$folderId`
was sent.

**The operation file was found by reflection** on `__METHOD__`, so renaming a
method silently lost its query.

**Two transports.** JSON went through a class extending the `Http` facade.
Uploads went through raw cURL, with no timeout, and the `operations` part of the
multipart request was assembled by splicing strings into a JSON template that
ended in a stray `$variables`. Neither could be faked.

**Errors were not errors.** The HTTP status was not checked. The message was the
first property of the first error. The token check built its exception without
throwing it, and the next line would have failed on a `null` token anyway.

**Responses were untyped.** Every method returned `Fluent` over `stdClass`, so a
caller wrote `$response->data->createFolder->id` and had to know the GraphQL
shape.

**The client kept per-request state.** The file, the signers and the post type
lived on the transport object, so it could not be reused safely across calls.

**The signer builder lost data.** The last signer was dropped unless
`setSigner()` was called once more, every signer's action was hard coded to
`SIGN`, and positions were sent as floats where the API takes strings.

**Tests reached the live API** with a real token, one ended in `dd()`, and they
used assertions PHPUnit has since removed.

## What this package keeps

| Idea | Kept as |
|---|---|
| One `.graphql` file per operation | the same, with variables only, an enum naming each file, shared fragments and validation against the schema ([0003](../decisions/0003-operations-live-in-graphql-files.md)) |
| Resource classes over one transport | the same, with the transport on Laravel's client ([0004](../decisions/0004-the-transport-is-laravels-http-client.md)) |
| The GraphQL multipart request for uploads | the same specification, through `attach()` |
| A fluent signer builder with several positions | typed value objects and enums ([0008](../decisions/0008-responses-are-typed-value-objects.md)) |
| A dedicated exception carrying the API's message | an exception per fault, and the error codes as an enum ([0005](../decisions/0005-exceptions-name-the-real-fault.md)) |
| Its map of error codes to Portuguese messages, never used | translations of the documented codes |
| Sandbox per call | per call, with the default in the config ([0006](../decisions/0006-sandbox-is-chosen-per-call.md)) |

## Why a new package rather than a v3

Nothing of its public API survives: not a class name, not a method, not a return
type, not the namespace, which carried the API version in it. A v3 would have
been a new package published under an old name, and the upgrade guide would
have been a list of every line to rewrite.
