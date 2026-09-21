<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Data\{EmailTemplate, Organization};
use LSNepomuceno\LaravelAutentique\Enums\EmailTemplateType;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(fn() => config()->set('autentique.token', 'the-token'));

it('reads the current organization with its groups', function () {
    answerValue(Operation::Organization, [
        'id' => 179,
        'uuid' => '91155c91-a411-4d93-b2a4-92e37548256b',
        'name' => 'Autentique',
        'cnpj' => '29.423.653/0001-65',
        'groups' => [['id' => 1, 'uuid' => 'g-1', 'name' => 'Administrador'], ['id' => 2, 'uuid' => 'g-2', 'name' => 'Sem grupo']],
    ]);

    $organization = Autentique::organizations()->current();

    expect($organization)->toBeInstanceOf(Organization::class)
        ->and($organization->id)->toBe(179)
        ->and($organization->groups)->toHaveCount(2)
        ->and($organization->groups[0]->name)->toBe('Administrador')
        ->and(sentOperation())->toBe('organization');
});

it('lists every organization of the account', function () {
    answerValue(Operation::Organizations, [
        ['id' => 179, 'uuid' => 'a', 'name' => 'Autentique', 'cnpj' => null],
        ['id' => 180, 'uuid' => 'b', 'name' => 'Another', 'cnpj' => null],
    ]);

    $organizations = Autentique::organizations()->list();

    expect($organizations)->toHaveCount(2)
        ->and($organizations[1]->name)->toBe('Another');
});

it('lists the email templates', function () {
    answerValue(Operation::EmailTemplates, [
        'has_more_pages' => false,
        'data' => [[
            'id' => 1234,
            'name' => 'Branded request',
            'type' => 'SOLICITATION',
            'email' => ['text' => 'Please sign', 'sender' => 'Legal', 'colors' => ['#000000', '#ffffff'], 'template' => '<p>…</p>'],
        ]],
    ]);

    $template = Autentique::organizations()->emailTemplates()->items[0];

    expect($template)->toBeInstanceOf(EmailTemplate::class)
        ->and($template->id)->toBe(1234)
        ->and($template->type)->toBe(EmailTemplateType::Solicitation)
        ->and($template->sender)->toBe('Legal')
        ->and($template->colors)->toBe(['#000000', '#ffffff'])
        ->and(sentVariables())->toBe(['limit' => 60, 'page' => 1]);
});

it('refuses a page that cannot exist', function () {
    Http::fake();

    Autentique::organizations()->emailTemplates(page: 0);
})->throws(InvalidInput::class);
