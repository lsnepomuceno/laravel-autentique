<?php

declare(strict_types=1);

use Illuminate\Support\Facades\{Event, Http};
use LSNepomuceno\LaravelAutentique\Data\Input\Signer;
use LSNepomuceno\LaravelAutentique\Enums\{Action, WebhookEventType};
use LSNepomuceno\LaravelAutentique\Events\AutentiqueWebhookReceived;
use LSNepomuceno\LaravelAutentique\Exceptions\{NotFound, ValidationFailed};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Testing\{AutentiqueFake, FakeWebhook};
use PHPUnit\Framework\ExpectationFailedException;

/**
 * The fake, as a consuming application uses it.
 */

beforeEach(fn() => Http::fake());

it('sends nothing, needs no token, and answers with what was sent', function () {
    config()->set('autentique.token', null);
    $fake = Autentique::fake();

    $document = Autentique::newDocument('Service agreement')
        ->file(memoryFile())
        ->signer(Signer::email('ana@example.com'))
        ->signer(Signer::link('Ronaldo', Action::SignAsWitness))
        ->send();

    expect($document->name)->toBe('Service agreement')
        ->and($document->id)->toStartWith('fake-document-')
        ->and($document->signatures)->toHaveCount(2)
        ->and($document->signatures[0]->email)->toBe('ana@example.com')
        ->and($document->signatures[1]->action)->toBe(Action::SignAsWitness)
        ->and($document->signatures[1]->link?->shortLink)->toStartWith('https://assina.ae/');

    $fake->assertDocumentSent()
        ->assertDocumentSentTimes(1)
        ->assertDocumentSent(fn(array $document, array $signers, ?string $file): bool => $document['name'] === 'Service agreement'
            && $signers[0]['email'] === 'ana@example.com'
            && $file === 'contract.pdf');

    Http::assertNothingSent();
});

it('answers every operation with something the package can read', function () {
    Autentique::fake();

    expect(Autentique::account()->me()->name)->toBe('Fake User')
        ->and(Autentique::documents()->find('doc-1')->id)->toBe('doc-1')
        ->and(Autentique::documents()->list()->items)->toBe([])
        ->and(Autentique::documents()->delete('doc-1'))->toBeTrue()
        ->and(Autentique::documents()->update('doc-1', new LSNepomuceno\LaravelAutentique\Data\Input\DocumentChanges(name: 'x'))->id)->toBe('doc-1')
        ->and(Autentique::signers()->add('doc-1', Signer::email('a@example.com'))->email)->toBe('a@example.com')
        ->and(Autentique::signers()->resend(['p1']))->toBeTrue()
        ->and(Autentique::signers()->link('p1')->shortLink)->not->toBeNull()
        ->and(Autentique::signers()->approveBiometric(1, 'p1')->publicId)->toBe('p1')
        ->and(Autentique::folders()->create('Contracts')->name)->toBe('Contracts')
        ->and(Autentique::folders()->find('f1')->id)->toBe('f1')
        ->and(Autentique::folders()->documents('f1')->total)->toBe(0)
        ->and(Autentique::organizations()->current()->name)->toBe('Fake organization')
        ->and(Autentique::organizations()->list())->toHaveCount(1)
        ->and(Autentique::organizations()->emailTemplates()->items)->toBe([]);
});

it('answers as the test says', function () {
    $fake = Autentique::fake()->respond(Operation::Documents, [
        'data' => [responseFixture('objects/document')],
        'has_more_pages' => false,
    ]);

    expect(Autentique::documents()->list()->items[0]->name)->toBe('Marketing contract');

    $fake->respond(Operation::DeleteDocument, fn(array $variables): bool => $variables['id'] === 'keep-me');

    expect(Autentique::documents()->delete('keep-me'))->toBeTrue()
        ->and(Autentique::documents()->delete('other'))->toBeFalse();
});

it('fails as the test says, and records the attempt', function () {
    $fake = Autentique::fake()->fail(Operation::Document, new NotFound('Document not found.'));

    expect(fn() => Autentique::documents()->find('gone'))->toThrow(NotFound::class);

    $fake->assertSent(Operation::Document, fn(array $variables): bool => $variables['id'] === 'gone');

    $fake->respond(Operation::Document, responseFixture('objects/document'));

    expect(Autentique::documents()->find('back')->name)->toBe('Marketing contract');
});

it('asserts signers and resends', function () {
    $fake = Autentique::fake();

    Autentique::signers()->add('doc-1', Signer::whatsapp('+5554999999999'));
    Autentique::signers()->resend(['p1', 'p2']);

    $fake->assertSignerAdded()
        ->assertSignerAdded(fn(string $documentId, array $signer): bool => $documentId === 'doc-1' && $signer['phone'] === '+5554999999999')
        ->assertResent()
        ->assertResent(['p1', 'p2'])
        ->assertNotSent(Operation::DeleteSigner)
        ->assertSentTimes(Operation::CreateSigner, 1);
});

it('fails an assertion that does not hold', function (Closure $assertion) {
    $fake = Autentique::fake();
    Autentique::signers()->resend(['p1']);

    expect(fn() => $assertion($fake))->toThrow(ExpectationFailedException::class);
})->with([
    'a document that was not sent' => [fn(AutentiqueFake $fake) => $fake->assertDocumentSent()],
    'nothing sent' => [fn(AutentiqueFake $fake) => $fake->assertNothingSent()],
    'resent to others' => [fn(AutentiqueFake $fake) => $fake->assertResent(['p2'])],
    'sent more times' => [fn(AutentiqueFake $fake) => $fake->assertSentTimes(Operation::ResendSignatures, 2)],
    'not sent, although it was' => [fn(AutentiqueFake $fake) => $fake->assertNotSent(Operation::ResendSignatures)],
]);

it('records a raw query and answers it as told', function () {
    $fake = Autentique::fake()->respondToQuery(fn(string $query, array $variables): array => ['echo' => $variables]);

    expect(Autentique::query('query { me { id } }', ['a' => 1]))->toBe(['echo' => ['a' => 1]])
        ->and($fake->sent()[0]->query)->toBe('query { me { id } }')
        ->and($fake->sent()[0]->operation)->toBeNull();
});

it('records the token a call was made with', function () {
    $fake = Autentique::fake();

    app(LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient::class)->withToken('someone-else')->send(Operation::Me);

    expect($fake->sent(Operation::Me)[0]->token)->toBe('someone-else');
});

it('lets a thrown validation failure reach the application as it would', function () {
    Autentique::fake()->fail(Operation::CreateFolder, new ValidationFailed('Autentique refused the request.'));

    Autentique::folders()->create('Contracts');
})->throws(ValidationFailed::class);

it('builds a signed webhook the route accepts', function () {
    Event::fake([AutentiqueWebhookReceived::class]);

    $webhook = FakeWebhook::make(WebhookEventType::DocumentFinished, ['id' => 'doc-1'], id: 'evt-1');

    $this->call('POST', '/webhooks/autentique', server: $webhook->server(), content: $webhook->body)->assertOk();

    Event::assertDispatched(fn(AutentiqueWebhookReceived $received): bool => $received->event->id === 'evt-1'
        && $received->event->type === WebhookEventType::DocumentFinished
        && $received->event->documentId() === 'doc-1');

    expect($webhook->headers())->toHaveKey('X-Autentique-Signature');
});

it('signs a webhook with another secret when told, which the route refuses', function () {
    $webhook = FakeWebhook::make(WebhookEventType::SignatureAccepted, secret: 'not-the-configured-one');

    $this->call('POST', '/webhooks/autentique', server: $webhook->server(), content: $webhook->body)->assertUnauthorized();
});
