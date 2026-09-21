# Testing

An application that sends documents has to test the code that sends them,
without a token, without spending credits and without messaging anyone.

## `Autentique::fake()`

```php
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

it('sends the agreement to the customer', function () {
    $autentique = Autentique::fake();

    $this->post('/agreements', ['customer' => $customer->id])->assertCreated();

    $autentique->assertDocumentSent(fn(array $document, array $signers, ?string $file) =>
        $document['name'] === 'Service agreement'
        && $signers[0]['email'] === $customer->email
        && $file === 'agreement.pdf'
    );
});
```

The fake takes the place of the package's GraphQL client, so the facade, the
builder and every `Api\` class reach it, and nothing reaches the network. It
needs no token.

## What it answers

Every operation gets an answer shaped as Autentique would shape it, built from
what was sent: a created document carries the name and the signers the
application gave it, each with a public id, and a signer reached by link gets a
link. A listing is an empty page, a delete is `true`.

To answer something else:

```php
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

$autentique = Autentique::fake()
    ->respond(Operation::Documents, ['data' => [/* documents, as Autentique returns them */], 'has_more_pages' => false])
    ->respond(Operation::DeleteDocument, fn(array $variables) => $variables['id'] !== 'locked');
```

The value goes under the operation's field, exactly as Autentique's `data`
would carry it. A closure receives the variables sent.

To fail:

```php
use LSNepomuceno\LaravelAutentique\Exceptions\NotFound;

Autentique::fake()->fail(Operation::Document, new NotFound('Document not found.'));
```

The call is recorded all the same.

## Assertions

| Assertion | |
|---|---|
| `assertDocumentSent(?Closure)` | a document was created; the closure gets the document, the signers and the file name |
| `assertDocumentSentTimes($n)`, `assertNoDocumentSent()` | |
| `assertSignerAdded(?Closure)` | the closure gets the document id and the signer |
| `assertResent(?array $publicIds)` | exactly these signatures, when given |
| `assertSent(Operation, ?Closure)` | any operation; the closure gets the variables sent |
| `assertNotSent(Operation)`, `assertSentTimes(Operation, $n)`, `assertNothingSent()` | |

`sent(?Operation)` returns every recorded call, as `Testing\SentOperation`, for
anything the assertions do not cover. `Autentique::query()` calls are recorded
with a null operation and their document; `respondToQuery()` answers them.

## Webhooks

`Testing\FakeWebhook` builds a webhook as Autentique would post it, signed with
the configured secret:

```php
use LSNepomuceno\LaravelAutentique\Enums\WebhookEventType;
use LSNepomuceno\LaravelAutentique\Testing\FakeWebhook;

$webhook = FakeWebhook::make(WebhookEventType::DocumentFinished, ['id' => $contract->autentique_id]);

$this->call('POST', '/webhooks/autentique', server: $webhook->server(), content: $webhook->body)
    ->assertOk();

expect($contract->fresh()->signed)->toBeTrue();
```

A test needs `AUTENTIQUE_WEBHOOK_SECRET` set, or `secret:` given, to be signed
at all. `headers()` returns the same as headers, for a client that takes them.

## `Http::fake()` works too

Every request the package makes goes through Laravel's HTTP client, so
`Http::fake()` and `Http::preventStrayRequests()` reach it as they reach
everything else. The fake above is the better tool for testing your own code;
`Http::fake()` is there when you want to see the requests themselves.
