# Errors

Every exception the package throws extends
`LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException`, so one `catch`
covers all of them. Each subclass names one fault
([0005](/decisions/0005-exceptions-name-the-real-fault)).

## Autentique reports most errors with HTTP 200

Only a rejected token (401) and a rate limit (429) arrive as HTTP statuses.
Everything else arrives with HTTP 200, as an `errors` array beside `data`, and
the package treats that array as a failure even when `data` is partly filled.
You never check a response for errors yourself: a method that returns, succeeded.

## What can be thrown

| Exception | When | Safe to send again? |
|---|---|---|
| `Unauthenticated` | the token is missing, wrong, expired or revoked (401), or an OAuth token lacks the scope (`Unauthorized` with 200) | after fixing the token |
| `RateLimited` | Autentique refused the request (429) and the configured retries were spent | yes, after `$retryAfter` seconds |
| `ValidationFailed` | a value was refused; the codes are per field | after fixing the value |
| `ResendThrottled` | every signature asked to be resent was resent too recently; nothing was sent | yes, later |
| `NotFound` | the document, folder or signature does not exist, or is not visible to the token's owner | no |
| `GraphQLError` | any other error Autentique reported, with its code when the package knows it | depends on the code |
| `TransportFailed` | the connection failed or timed out, or the answer was not GraphQL (a 5xx, a proxy page) | **only if the operation is safe to repeat** |
| `MissingToken` | no token is configured; nothing was sent | after configuring it |
| `InvalidInput` | a value the package refused before sending, because Autentique documents it would refuse or silently change it | after fixing the value |
| `InvalidOperation` | an operation file is missing or broken; a defect in the package | no, report it |
| `UnexpectedResponse` | Autentique answered without a field the package cannot do without; the API and the package disagree about the schema | no, report it |

`Unauthenticated`, `RateLimited`, `ValidationFailed`, `ResendThrottled`, `NotFound`, `GraphQLError` and `TransportFailed` extend `RequestFailed`, and carry:

| Property | |
|---|---|
| `$operation` | the operation's name, `createDocument` |
| `$status` | the HTTP status, when there was one |
| `$requestId` | Autentique's `X-Attq-Request-Id`, worth quoting to their support |
| `$errors` | every entry of `errors`, as `Data\ApiError`, not only the first |

## Validation

```php
use LSNepomuceno\LaravelAutentique\Exceptions\ValidationFailed;

try {
    // …
} catch (ValidationFailed $exception) {
    $exception->messages();
    // ['folder.name' => ["Can't have less than 3 characters."]]

    foreach ($exception->violations() as $violation) {
        $violation->field;      // 'folder.name'
        $violation->code;       // ErrorCode::MustBeAtLeastCharacters
        $violation->parameter;  // '3'
    }
}
```

`messages()` is grouped the way Laravel's own validator groups errors, and each
message follows the application's locale. English and Brazilian Portuguese
ship, with Autentique's own wording where its documentation has one.

A code the package does not know still arrives, as `$violation->rawCode`, with
`$violation->code` null.

## Rate limits and retries

Autentique allows 10, 60 or 200 requests per minute per token, by plan. When it
refuses one, the package waits and tries again, honouring `Retry-After`, up to
`autentique.retry.times` times (2 by default). Only then does `RateLimited`
reach you.

**A timeout and a server error are never retried**, because they may have been
processed, and repeating `createDocument` after one can create a second billed
document and send every signer a second request
([0009](/decisions/0009-only-a-refused-request-is-retried)).

The same caution applies to your own code: a queued job that sends documents
should not retry itself blindly on `TransportFailed`. Look the document up
first, or let a person decide.

```php
use LSNepomuceno\LaravelAutentique\Exceptions\RateLimited;

public function handle(): void
{
    try {
        // …
    } catch (RateLimited $exception) {
        $this->release($exception->retryAfter ?? 60);
    }
}
```
