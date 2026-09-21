<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Data\Input\{SecurityVerification, Signer};
use LSNepomuceno\LaravelAutentique\Data\{Link, Signature};
use LSNepomuceno\LaravelAutentique\Enums\VerificationType;
use LSNepomuceno\LaravelAutentique\Exceptions\{GraphQLError, InvalidInput, ResendThrottled};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(fn() => config()->set('autentique.token', 'the-token'));

it('adds a signer to a document', function () {
    answerWith(Operation::CreateSigner, 'signature');

    $signature = Autentique::signers()->add(
        'doc-1',
        Signer::email('late@example.com')->withVerification(SecurityVerification::pfFacial()->withoutFallback()),
    );

    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->publicId)->toBe('434fcd4c6d0c11eea3c542010a2b60c6')
        ->and(sentOperation())->toBe('createSigner')
        ->and(sentVariables())->toBe([
            'document_id' => 'doc-1',
            'signer' => [
                'email' => 'late@example.com',
                'action' => 'SIGN',
                'security_verifications' => [['type' => 'PF_FACIAL', 'fallback_behavior' => 'DISABLE_FALLBACK']],
            ],
        ]);
});

it('removes a signer', function () {
    answerValue(Operation::DeleteSigner, true);

    expect(Autentique::signers()->remove('pub-1', 'doc-1'))->toBeTrue()
        ->and(sentVariables())->toBe(['public_id' => 'pub-1', 'document_id' => 'doc-1']);
});

it('resends requests', function () {
    answerValue(Operation::ResendSignatures, true);

    expect(Autentique::signers()->resend(['pub-1', 'pub-2']))->toBeTrue()
        ->and(sentVariables())->toBe(['public_ids' => ['pub-1', 'pub-2']]);
});

it('says when every signature was resent too recently', function () {
    Http::fake(['*' => Http::response(responseFixture('errors/coded'))]);

    $exception = thrown(ResendThrottled::class, fn() => Autentique::signers()->resend(['pub-1']));

    expect($exception->operation)->toBe('resendSignatures')
        ->and($exception->getPrevious())->toBeInstanceOf(GraphQLError::class);
});

it('lets any other resend failure through as it was', function () {
    Http::fake(['*' => Http::response(['errors' => [['message' => 'something else']]])]);

    Autentique::signers()->resend(['pub-1']);
})->throws(GraphQLError::class, 'something else');

it('refuses to resend nothing', function () {
    Http::fake();

    expect(fn() => Autentique::signers()->resend([]))->toThrow(InvalidInput::class);

    Http::assertNothingSent();
});

it('creates a signing link', function () {
    answerValue(Operation::CreateLinkToSignature, ['id' => 'l1', 'short_link' => 'https://assina.ae/A9pQN40FuNc10Na8K8']);

    $link = Autentique::signers()->link('pub-1');

    expect($link)->toBeInstanceOf(Link::class)
        ->and($link->shortLink)->toBe('https://assina.ae/A9pQN40FuNc10Na8K8')
        ->and(sentVariables())->toBe(['public_id' => 'pub-1']);
});

it('approves and rejects a manual verification', function (Closure $decide, Operation $operation) {
    answerWith($operation, 'signature');

    /** @var Signature $signature */
    $signature = $decide();

    expect($signature)->toBeInstanceOf(Signature::class)
        ->and($signature->verifications[0]->type)->toBe(VerificationType::Manual)
        ->and(sentOperation())->toBe($operation->operationName())
        ->and(sentVariables())->toBe(['verification_id' => 430555, 'public_id' => 'pub-1']);
})->with([
    'approve' => [fn(): Signature => Autentique::signers()->approveBiometric(430555, 'pub-1'), Operation::ApproveBiometric],
    'reject' => [fn(): Signature => Autentique::signers()->rejectBiometric(430555, 'pub-1'), Operation::RejectBiometric],
]);
