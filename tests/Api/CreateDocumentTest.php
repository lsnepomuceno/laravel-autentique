<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use LSNepomuceno\LaravelAutentique\Data\Input\{NewDocument, Position, SecurityVerification, Signer};
use LSNepomuceno\LaravelAutentique\Enums\{Action,
    DeliveryMethod,
    Footer,
    FooterType,
    PositionElement,
    Reminder,
    SignerType,
    VerificationType};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(function () {
    config()->set('autentique.token', 'the-token');
    answerWith(Operation::CreateDocument, 'document');
});

/**
 * A PDF on disk, for the tests that need a path.
 */
function pdfOnDisk(): string
{
    $path = sys_get_temp_dir() . '/autentique-' . bin2hex(random_bytes(4)) . '.pdf';
    file_put_contents($path, '%PDF-1.7 test');

    return $path;
}

it('creates a document from a path, sending the signers and the file', function () {
    $document = Autentique::newDocument('Marketing contract')
        ->file(pdfOnDisk())
        ->signer(Signer::email('ana@example.com')->withPosition(Position::signature(5, 90)))
        ->signer(Signer::link('Ronaldo', Action::SignAsWitness))
        ->send();

    expect(sentVariables())->toBe([
        'document' => ['name' => 'Marketing contract'],
        'signers' => [
            ['email' => 'ana@example.com', 'action' => 'SIGN', 'positions' => [['x' => '5.0', 'y' => '90.0', 'z' => 1, 'element' => 'SIGNATURE']]],
            ['name' => 'Ronaldo', 'action' => 'SIGN_AS_A_WITNESS'],
        ],
        'sandbox' => false,
        'file' => null,
    ])->and(multipartParts(lastRequest())['file']['contents'])->toBe('%PDF-1.7 test')
        ->and($document->id)->toBe('89c7d2ab31f9f5a13b3d20ecf53319af387e54d240ae7be993');
});

it('reads the answer into a document with its signatures', function () {
    $document = Autentique::newDocument('Marketing contract')->file(pdfOnDisk())->signer(Signer::email('a@example.com'))->send();

    $first = $document->signatures[0];
    $second = $document->signatures[1];

    expect($document->name)->toBe('Marketing contract')
        ->and($document->reminder)->toBe(Reminder::Weekly)
        ->and($document->refusable)->toBeTrue()
        ->and($document->sandbox)->toBeTrue()
        ->and($document->showAuditPage)->toBeFalse()
        ->and($document->footer)->toBe(Footer::Bottom)
        ->and($document->createdAt?->toIso8601ZuluString())->toBe('2023-10-17T16:43:13Z')
        ->and($document->deadlineAt?->toIso8601ZuluString())->toBe('2023-11-24T02:59:59Z')
        ->and($document->files?->signed)->toEndWith('/assinado.pdf')
        ->and($document->isSigned())->toBeFalse()
        ->and($first->publicId)->toBe('434fcd4c6d0c11eea3c542010a2b60c6')
        ->and($first->action)->toBe(Action::Sign)
        ->and($first->deliveryMethod)->toBe(DeliveryMethod::Email)
        ->and($first->type)->toBe(SignerType::Standard)
        ->and($first->user?->id)->toBe('1ac41a793ed27015abd0a381eb2846e1c3e7fe01')
        ->and($first->emailEvents?->opened?->toDateTimeString())->toBe('2023-10-17 16:50:02')
        ->and($first->positions[0]->element)->toBe(PositionElement::Signature)
        ->and($first->positions[0]->x)->toBe(5.0)
        ->and($first->verifications[0]->id)->toBe(430555)
        ->and($first->verifications[0]->type)->toBe(VerificationType::Manual)
        ->and($first->verifications[0]->images)->toHaveKeys(['front', 'selfie'])
        ->and($first->verifications[0]->maxAttempts)->toBe(3)
        ->and($first->viewed?->geolocation?->city)->toBe('Erechim')
        ->and($first->viewed?->geolocation?->latitude)->toBe(-27.6767)
        ->and($first->hasSigned())->toBeFalse()
        ->and($second->link?->shortLink)->toBe('https://assina.ae/A9pQN40FuNc10Na8K8')
        ->and($second->user)->toBeNull()
        ->and($document->signature('93115640-df08-11ef-903c-0242ac140004'))->toBe($second)
        ->and($document->signature('nobody'))->toBeNull();
});

it('sends every option the builder was given', function () {
    Autentique::newDocument('Contract')
        ->file(pdfOnDisk())
        ->signer(Signer::whatsapp('+5554999999999'))
        ->message('Please sign')
        ->reminder(Reminder::Daily)
        ->sortable()
        ->refusable()
        ->stopOnRejected()
        ->qualified()
        ->scrollingRequired()
        ->footer(Footer::Left, FooterType::Mini)
        ->newSignatureStyle()
        ->withoutAuditPage()
        ->ignoreCpf()
        ->ignoreBirthdate()
        ->emailTemplate(12)
        ->deadline(CarbonImmutable::parse('2030-01-01T00:00:00Z'))
        ->replyTo('reply@example.com')
        ->watchers(['w@example.com'])
        ->cc(['cc@example.com'])
        ->sandbox()
        ->inFolder('folder-1')
        ->forOrganization(179)
        ->send();

    $variables = sentVariables();

    expect($variables['document'])->toBe([
        'name' => 'Contract',
        'message' => 'Please sign',
        'reminder' => 'DAILY',
        'sortable' => true,
        'footer' => 'LEFT',
        'footer_type' => 'MINI',
        'refusable' => true,
        'qualified' => true,
        'scrolling_required' => true,
        'stop_on_rejected' => true,
        'new_signature_style' => true,
        'show_audit_page' => false,
        'ignore_cpf' => true,
        'ignore_birthdate' => true,
        'email_template_id' => 12,
        'deadline_at' => '2030-01-01T00:00:00.000Z',
        'reply_to' => 'reply@example.com',
        'watchers' => ['w@example.com'],
        'cc' => [['email' => 'cc@example.com']],
    ])->and($variables['sandbox'])->toBeTrue()
        ->and($variables['folder_id'])->toBe('folder-1')
        ->and($variables['organization_id'])->toBe(179);
});

it('makes documents sandbox by default when the configuration says so', function () {
    config()->set('autentique.sandbox', true);

    Autentique::newDocument('x')->file(pdfOnDisk())->signer(Signer::email('a@example.com'))->send();

    expect(sentVariables()['sandbox'])->toBeTrue();
});

it('lets a call override the configured sandbox', function () {
    config()->set('autentique.sandbox', true);

    Autentique::newDocument('x')->file(pdfOnDisk())->signer(Signer::email('a@example.com'))->sandbox(false)->send();

    expect(sentVariables()['sandbox'])->toBeFalse();
});

it('uploads a file received in the request', function () {
    $upload = UploadedFile::fake()->createWithContent('from-the-form.pdf', '%PDF-1.7 upload');

    Autentique::newDocument('x')->file($upload)->signer(Signer::email('a@example.com'))->send();

    expect(multipartParts(lastRequest())['file'])->toBe(['contents' => '%PDF-1.7 upload', 'filename' => 'from-the-form.pdf']);
});

it('streams a file from a Storage disk', function () {
    Storage::fake('contracts');
    Storage::disk('contracts')->put('2026/agreement.pdf', '%PDF-1.7 disk');

    Autentique::newDocument('x')
        ->file(Autentique::fromDisk('contracts', '2026/agreement.pdf', 'Agreement.pdf'))
        ->signer(Signer::email('a@example.com'))
        ->send();

    expect(multipartParts(lastRequest())['file'])->toBe(['contents' => '%PDF-1.7 disk', 'filename' => 'Agreement.pdf']);
});

it('names a file from a path as asked', function () {
    Autentique::newDocument('x')->file(Autentique::fromPath(pdfOnDisk(), 'Named.pdf'))->signer(Signer::email('a@example.com'))->send();

    expect(multipartParts(lastRequest())['file']['filename'])->toBe('Named.pdf');
});

it('sends a WhatsApp Flow document from Markdown', function () {
    Autentique::newDocument('x')
        ->file(memoryFile('flow.md', '# Terms'))
        ->signer(Signer::whatsapp('+5554999999999'))
        ->whatsappFlow()
        ->send();

    expect(sentVariables()['type'])->toBe('WHATSAPP_FLOW');
});

it('can be called without the builder', function () {
    $document = Autentique::documents()->create(
        new NewDocument('Direct', refusable: true),
        [Signer::email('a@example.com')->withVerification(SecurityVerification::sms())],
        memoryFile(),
        sandbox: true,
    );

    expect($document->id)->not->toBeEmpty()
        ->and(sentVariables()['document'])->toBe(['name' => 'Direct', 'refusable' => true])
        ->and(sentVariables()['signers'])->toBe([['email' => 'a@example.com', 'action' => 'SIGN', 'security_verifications' => [['type' => 'SMS']]]]);
});

it('refuses, before sending anything, what Autentique would refuse', function (Closure $send, string $message) {
    expect($send)->toThrow(InvalidInput::class, $message);

    Http::assertNothingSent();
})->with([
    'no file' => [fn() => Autentique::newDocument('x')->signer(Signer::email('a@example.com'))->send(), 'has no file'],
    'no signer' => [fn() => Autentique::newDocument('x')->file(memoryFile())->send(), 'at least one signer'],
    'a missing path' => [fn() => Autentique::newDocument('x')->file('/nowhere/contract.pdf'), 'no readable file'],
    'a missing disk file' => [function () {
        Storage::fake('contracts');
        Autentique::fromDisk('contracts', 'missing.pdf');
    }, 'no file at missing.pdf'],
    'WhatsApp Flow with a PDF' => [
        fn() => Autentique::newDocument('x')->file(memoryFile())->signer(Signer::email('a@example.com'))->whatsappFlow()->send(),
        'is a Markdown file',
    ],
    'Markdown without WhatsApp Flow' => [
        fn() => Autentique::newDocument('x')->file(memoryFile('terms.md'))->signer(Signer::email('a@example.com'))->send(),
        'only accepted as a WhatsApp Flow',
    ],
    'a combination Autentique overrides' => [
        fn() => Autentique::newDocument('x')->file(memoryFile())->signer(Signer::email('a@example.com'))->stopOnRejected()->send(),
        'needs a refusable document',
    ],
]);
