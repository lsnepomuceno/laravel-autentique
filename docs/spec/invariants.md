# Invariants

Rules that break the product, or the project, when violated. Short on purpose:
this file is meant to be read whole before touching the transport, the
operations, the config file or the dependency list.

Everything here is enforced by a test, a tool, or an explicit review step, and
each rule names which.

---

## 1. Every request goes through the one injected HTTP client

The package reaches Autentique through a single class built on
`Illuminate\Http\Client\Factory`, resolved from the container. Nothing else in
`src/` opens a connection: not the `Http` facade, not Guzzle, not cURL, not a
socket.

**A request that bypasses the client cannot be faked.** A consuming
application tests its own signing flow with `Http::fake()` and refuses anything
unexpected with `Http::preventStrayRequests()`. A second transport would leave
those tests green while the code under them reaches the real API, with a real
token, spending real credits
([0004](../decisions/0004-the-transport-is-laravels-http-client.md)).

*Enforced by* `tests/Project/ArchTest.php` (`only the GraphQL client reaches
the network`).

---

## 2. Nothing is interpolated into an operation

An operation is the text of its `.graphql` file, sent as written. Every value
travels in the `variables` object. No operation is built, concatenated or
formatted at runtime.

**A value in the text is GraphQL injection.** A document name carrying a quote
changes the operation. laravel-autentique-v2 did exactly this, and the same
mechanism is why its `changeFolder` sent the literal string `$folderId` to the
API ([0003](../decisions/0003-operations-live-in-graphql-files.md)).

*Enforced by* `tests/Project/ArchTest.php` (`writes no GraphQL operation in
PHP`), which reads every string literal and heredoc in `src/`.

---

## 3. The token and the webhook secret never leave the request

Not in an exception message, not in an exception's context, not in a log line,
not in a stack trace, not in a dumped request.

**An exception travels further than a request.** It reaches an error tracker,
a log aggregator and a support ticket, and a token found there is a token that
signs documents in the owner's name.

*Enforced by* `tests/Project/ArchTest.php`, which requires
`#[\SensitiveParameter]` on every parameter named `token`, `secret` or
`password`, so PHP replaces the value in every trace it builds. What a message
or a log line says is checked at review, and by the tests of each exception.

---

## 4. The config file holds scalars, and nothing else

`config/autentique.php` is an array of strings, numbers, booleans and nulls,
and lists of them.
Enums and value objects are built from it at resolution time.

**A config file carrying an object cannot be cached.** `config:cache`
serialises the array; an enum instance fails there, and it fails in the
consuming application, on a command nobody runs until deployment.

*Enforced by* `tests/Container/ServiceTest.php`.

---

## 5. The suite never reaches Autentique

Every test runs with `Http::preventStrayRequests()`, so a request nobody faked
fails the test instead of leaving the machine. There is no token in the
repository, no network group and no test that needs one.

**A suite that calls the API is a suite that depends on an account**, on its
credits, on its rate limit and on the API being up. laravel-autentique-v2's
tests did, and could not run without the maintainer's token.

*Enforced by* `tests/TestCase.php`, and asserted by
`tests/Container/StrayRequestTest.php`.

---

## 6. PSR-4 autoloading is case sensitive

A class whose file name differs from it only in case autoloads on macOS and
fails in production on Linux. Name the file exactly as the class.

*Enforced by* the CI matrix, which runs on Linux.
