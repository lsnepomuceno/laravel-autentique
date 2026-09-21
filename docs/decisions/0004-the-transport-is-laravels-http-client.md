# 0004: The transport is Laravel's HTTP client, for JSON and for uploads alike

**Status:** decided before the code, tracked by #8.

## Context

Autentique receives two kinds of request, both as `POST` to the same endpoint:

- **JSON**, `{query, variables, operationName}`, for every operation but one;
- **multipart**, following the
  [GraphQL multipart request specification](https://github.com/jaydenseric/graphql-multipart-request-spec),
  for `createDocument`, whose `file` argument is an `Upload`. The parts are
  `operations` (the JSON above, with `file: null` in the variables), `map`
  (`{"file": ["variables.file"]}`) and `file` (the bytes).

`laravel-autentique-v2` used two transports for these. JSON went through a class
that extended the `Http` facade. Uploads went through raw cURL, with
`CURLOPT_TIMEOUT => 0` and the `operations` part assembled by splicing strings.
None of it could be faked, so its tests reached the live API with a real token.

## Decision

**Every request goes through one class built on
`Illuminate\Http\Client\Factory`, injected, and nothing else in `src/` opens a
connection.** Uploads use the client's `attach()`, which produces the multipart
body the specification describes.

The token, timeout and retry policy come from the config file. The token is sent
as `Authorization: Bearer`, and is marked `#[\SensitiveParameter]` wherever it is
an argument.

## Consequences

- `Http::fake()` in a consuming application intercepts every request the
  package makes, uploads included, and `Http::preventStrayRequests()` refuses
  them. That is what lets an application test its own signing flow without a
  token and without spending credits.
- The application's HTTP middleware, logging and proxy settings see the
  traffic, as they see every other outgoing call.
- A file on a `Storage` disk is attached as a stream, so a document on S3 is not
  copied to a local file first.
- The one-class rule is checked by an architecture test, so a second transport
  cannot arrive unnoticed.

## Alternatives rejected

| | Why not |
|---|---|
| Guzzle directly | What Laravel's client wraps, without the fake, the events or the configuration a Laravel application already has |
| A PSR-18 client behind a contract | See [0001](0001-one-laravel-package.md): a second code path that `Http::fake()` does not reach |
| Keeping cURL for uploads | Unfakeable, and the upload is the operation an application most needs to test without reaching the API |

## Outcome

Shipped in #8 as decided. `GraphQL\Client` builds every request from the
injected `Illuminate\Http\Client\Factory`, JSON through `asJson()` and uploads
through three `attach()` calls in the order the specification requires. The
contract `Contracts\GraphQLClient` sits in front of it so the fake can take its
place.

One detail the record did not anticipate: the response is decoded in the client
and handed to `GraphQL\ResponseParser` as a status, a body and two headers,
rather than as a response object. That keeps the parser free of the HTTP layer,
so the architecture test holding invariant 1 needs no exception for it, and the
parser is tested without faking HTTP at all.
