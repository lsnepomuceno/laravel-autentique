<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LSNepomuceno\LaravelAutentique\Data\{ApiUsage, ChildOrganization, CorporatePlan, OrganizationMember, Subscription, WebhookEndpoint};
use LSNepomuceno\LaravelAutentique\Data\Input\{ChildOrganizationChanges,
    Locale,
    NewChildOrganization,
    NewMember,
    NewSubscriptionPlan,
    PrefilledFields,
    Signer};
use LSNepomuceno\LaravelAutentique\Enums\{ChildOrganizationPlan,
    CreditsRecurrence,
    CustomPlan,
    DateFormat,
    IntervalType,
    Language,
    MemberPermission,
    Tier,
    WebhookEndpointType,
    WebhookEventType,
    WebhookFormat};
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidInput;
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

beforeEach(fn() => config()->set('autentique.token', 'the-token'));

/**
 * A child organization as the Corporate documentation shows one.
 *
 * @return array<string, mixed>
 */
function childOrganization(): array
{
    return [
        'id' => 123,
        'uuid' => 'c1',
        'name' => 'Org filha 1',
        'cnpj' => null,
        'plan' => 'FREE',
        'default_email_templates_ids' => [10, 11],
        'created_at' => '2026-08-01T10:00:00.000000Z',
        'settings' => ['webhook_url' => null],
        'groups' => [['id' => 1, 'uuid' => 'g1', 'name' => 'Administrador'], ['id' => 2, 'uuid' => 'g2', 'name' => 'Sem grupo']],
    ];
}

it('sends Corporate operations to the Corporate endpoint', function () {
    answerValue(Operation::CorporateOrganizations, [childOrganization()]);

    $organizations = Autentique::corporate()->organizations(name: 'Org', from: CarbonImmutable::parse('2024-04-01'));

    expect($organizations[0])->toBeInstanceOf(ChildOrganization::class)
        ->and($organizations[0]->plan)->toBe(ChildOrganizationPlan::Free)
        ->and($organizations[0]->defaultEmailTemplateIds)->toBe([10, 11])
        ->and($organizations[0]->groups)->toHaveCount(2)
        ->and(sentVariables())->toBe(['name' => 'Org', 'start_date' => '2024-04-01', 'limit' => 60, 'page' => 1]);

    Http::assertSent(fn(Request $request): bool => $request->url() === 'https://api.autentique.com.br/v2/graphql/corporate');
});

it('creates and updates a child organization', function () {
    answerValue(Operation::CorporateCreateOrganization, childOrganization());

    Autentique::corporate()->createOrganization(new NewChildOrganization(
        name: 'Org filha 1',
        locale: new Locale('BR', Language::PortugueseBrazil, 'America/Sao_Paulo', DateFormat::DayMonthYear),
        defaultEmailTemplateIds: [10, 11],
    ));

    expect(sentVariables())->toBe(['organization' => [
        'name' => 'Org filha 1',
        'locale' => ['country' => 'BR', 'language' => 'pt-BR', 'timezone' => 'America/Sao_Paulo', 'date_format' => 'DD_MM_YYYY'],
        'default_email_templates_ids' => [10, 11],
    ]]);
});

it('updates a child organization', function () {
    answerValue(Operation::CorporateUpdateOrganization, childOrganization());

    Autentique::corporate()->updateOrganization(123, new ChildOrganizationChanges(name: 'Org filha 2', webhookUrl: 'https://example.com/hook'));

    expect(sentVariables())->toBe(['id' => 123, 'organization' => ['name' => 'Org filha 2', 'settings' => ['webhook_url' => 'https://example.com/hook']]]);
});

it('changes a child organization plan, reading the subscription back', function () {
    answerValue(Operation::CorporateUpdateOrganizationPlan, [...childOrganization(), 'subscription' => ['name' => 'Professional', 'expires_at' => '2026-10-21T00:00:00.000000Z']]);

    $organization = Autentique::corporate()->changePlan(123, CustomPlan::Professional, daysUntilExpiration: 30);

    expect($organization->subscription?->name)->toBe('Professional')
        ->and($organization->subscription?->expiresAt?->toDateString())->toBe('2026-10-21')
        ->and(sentVariables())->toBe(['organization_id' => 123, 'plan' => 'PROFESSIONAL', 'days_until_expiration' => 30]);
});

it('deletes a child organization and a member', function (Closure $call, Operation $operation, array $variables) {
    answerValue($operation, true);

    expect($call())->toBeTrue()
        ->and(sentVariables())->toBe($variables);
})->with([
    'organization' => [fn() => Autentique::corporate()->deleteOrganization(123), Operation::CorporateDeleteOrganization, ['id' => 123]],
    'member' => [fn() => Autentique::corporate()->removeMember(123, '1234'), Operation::CorporateDeleteMember, ['organization_id' => 123, 'user_id' => '1234']],
]);

it('lists members, each with the token that acts as them', function () {
    answerValue(Operation::CorporateOrganizationMembers, [[
        'id' => 'm1',
        'user' => ['id' => '1234', 'name' => 'João Teste', 'email' => 'joao@example.com', 'cpf' => null, 'birthday' => '1995-11-24'],
        'group' => ['id' => 2, 'uuid' => 'g2', 'name' => 'Sem grupo'],
        'api_token' => ['access_token' => 'member-token'],
    ]]);

    $member = Autentique::corporate()->members(123)[0];

    expect($member)->toBeInstanceOf(OrganizationMember::class)
        ->and($member->userId)->toBe('1234')
        ->and($member->birthday?->toDateString())->toBe('1995-11-24')
        ->and($member->group?->name)->toBe('Sem grupo')
        ->and($member->apiToken)->toBe('member-token')
        ->and(sentVariables())->toBe(['organization_id' => 123, 'limit' => 60, 'page' => 1]);
});

it('adds and updates a member with permissions', function () {
    answerValue(Operation::CorporateCreateMember, ['id' => 'm1', 'user' => ['id' => '1234']]);

    Autentique::corporate()->addMember(123, new NewMember(
        name: 'João Teste',
        email: 'joao@example.com',
        birthday: CarbonImmutable::parse('1995-11-24'),
        language: Language::EnglishUnitedStates,
        permissions: [MemberPermission::CreateDocuments, MemberPermission::ViewDocumentsOrganization],
    ));

    expect(sentVariables())->toBe(['organization_id' => 123, 'member' => [
        'name' => 'João Teste',
        'email' => 'joao@example.com',
        'birthday' => '1995-11-24',
        'locale' => ['language' => 'en-US'],
        'permissions' => ['create_documents' => true, 'view_documents_oz' => true],
    ]]);
});

it('updates a member', function () {
    answerValue(Operation::CorporateUpdateMember, ['id' => 'm1', 'user' => ['id' => '1234']]);

    Autentique::corporate()->updateMember(123, '1234', new NewMember(groupId: 1));

    expect(sentVariables())->toBe(['organization_id' => 123, 'user_id' => '1234', 'member' => ['group_id' => 1]]);
});

it('creates a login code for the dashboard in an iframe', function () {
    answerValue(Operation::CorporateCreateLoginCode, 'LOGIN_TOKEN');

    expect(Autentique::corporate()->loginCode(123, '1234'))->toBe('LOGIN_TOKEN');
});

it('registers a webhook endpoint and returns its secret', function () {
    answerValue(Operation::CorporateCreateEndpoint, [
        'secret' => 'the-new-secret',
        'webhook_endpoint' => ['id' => 'e1', 'url' => 'https://example.com/hooks', 'active' => true, 'events' => ['SIGNATURE_CREATED', 'SIGNATURE_ACCEPTED']],
    ]);

    $endpoint = Autentique::corporate()->createWebhookEndpoint(
        123,
        'https://example.com/hooks',
        'Signatures',
        WebhookEndpointType::Signature,
        [WebhookEventType::SignatureCreated, WebhookEventType::SignatureAccepted],
        folderId: 'c8e96bce',
    );

    expect($endpoint)->toBeInstanceOf(WebhookEndpoint::class)
        ->and($endpoint->secret)->toBe('the-new-secret')
        ->and($endpoint->events)->toBe(['SIGNATURE_CREATED', 'SIGNATURE_ACCEPTED'])
        ->and(sentVariables())->toBe([
            'organization_id' => 123,
            'url' => 'https://example.com/hooks',
            'format' => 'JSON',
            'type' => 'SIGNATURE',
            'name' => 'Signatures',
            'events' => ['SIGNATURE_CREATED', 'SIGNATURE_ACCEPTED'],
            'folder_id' => 'c8e96bce',
        ]);
});

it('registers the two events the Corporate documentation leaves out of its list', function () {
    answerValue(Operation::CorporateCreateEndpoint, ['secret' => 's', 'webhook_endpoint' => ['id' => 'e1']]);

    Autentique::corporate()->createWebhookEndpoint(1, 'https://x.test', 'x', WebhookEndpointType::Signature, [
        WebhookEventType::SignatureDeliveryFailed,
        WebhookEventType::SignatureBiometricReset,
    ], WebhookFormat::UrlEncoded);

    expect(sentVariables()['events'])->toBe(['SIGNATURE_DELIVERY_FAILED', 'SIGNATURE_BIOMETRIC_RESET'])
        ->and(sentVariables()['format'])->toBe('URLENCODED');
});

it('refuses an endpoint the API would refuse or ignore', function (Closure $create, string $message) {
    Http::fake();

    expect($create)->toThrow(InvalidInput::class, $message);

    Http::assertNothingSent();
})->with([
    'no events' => [fn() => Autentique::corporate()->createWebhookEndpoint(1, 'https://x.test', 'x', WebhookEndpointType::Member, []), 'at least one event'],
    'another resource' => [
        fn() => Autentique::corporate()->createWebhookEndpoint(1, 'https://x.test', 'x', WebhookEndpointType::Member, [WebhookEventType::DocumentCreated]),
        'cannot listen to document.created',
    ],
]);

it('reads the plans of child organizations', function () {
    answerValue(Operation::CorporateOrganizationsPlans, [['organization_id' => 1, 'subscription' => ['name' => 'Free']]]);

    $plans = Autentique::corporate()->plans([1, 2, 3]);

    expect($plans[0]->organizationId)->toBe(1)
        ->and($plans[0]->planName)->toBe('Free')
        ->and(sentVariables())->toBe(['organizations_ids' => [1, 2, 3]]);
});

it('reads the API usage of a child organization', function () {
    answerValue(Operation::CorporateApiUsage, [
        'pricing' => [['valid_since' => '2026-01-01', 'currency' => 'BRL', 'items' => ['create_document' => 0.06, 'email' => 0.013, 'sms' => null]]],
        'usage' => [['day' => '2026-08-01', 'items' => ['create_document' => 12, 'email' => 30]]],
    ]);

    $usage = Autentique::corporate()->apiUsage(123, CarbonImmutable::parse('2026-08-01'), CarbonImmutable::parse('2026-08-24'));

    expect($usage)->toBeInstanceOf(ApiUsage::class)
        ->and($usage->pricing[0]['currency'])->toBe('BRL')
        ->and($usage->pricing[0]['prices']->createDocument)->toBe(0.06)
        ->and($usage->pricing[0]['prices']->sms)->toBeNull()
        ->and($usage->days[0]['day']?->toDateString())->toBe('2026-08-01')
        ->and($usage->days[0]['used']->email)->toBe(30.0)
        ->and(sentVariables())->toBe(['organization_id' => 123, 'start_date' => '2026-08-01', 'end_date' => '2026-08-24']);
});

it('manages custom plans', function () {
    $plan = ['id' => 'p1', 'display_name' => 'Gold', 'document_amount' => 100, 'credit_amount' => 50, 'credits_recurrence_method' => 'DETACHED', 'interval' => 1, 'interval_type' => 'MONTH', 'tier' => 'PROFESSIONAL'];
    answerValue(Operation::CorporateCreateSubscriptionPlan, $plan);

    $created = Autentique::corporate()->createSubscriptionPlan(new NewSubscriptionPlan(
        'Gold',
        100,
        50,
        interval: 1,
        intervalType: IntervalType::Month,
        tier: Tier::Professional,
        creditsRecurrence: CreditsRecurrence::Detached,
    ));

    expect($created)->toBeInstanceOf(CorporatePlan::class)
        ->and($created->intervalType)->toBe(IntervalType::Month)
        ->and($created->creditsRecurrence)->toBe(CreditsRecurrence::Detached)
        ->and(sentVariables())->toBe(['plan' => [
            'display_name' => 'Gold',
            'document_amount' => 100,
            'credit_amount' => 50,
            'interval' => 1,
            'interval_type' => 'MONTH',
            'tier' => 'PROFESSIONAL',
            'credits_recurrence_method' => 'DETACHED',
        ]]);
});

it('lists, updates and assigns custom plans', function (Closure $call, Operation $operation, mixed $answer, array $variables, string $class) {
    /** @var class-string $class */
    answerValue($operation, $answer);

    $result = $call();

    expect(is_array($result) ? $result[0] : $result)->toBeInstanceOf($class)
        ->and(sentVariables())->toBe($variables);
})->with([
    'list' => [
        fn() => Autentique::corporate()->subscriptionPlans(),
        Operation::CorporateSubscriptionPlans,
        [['id' => 'p1']],
        ['limit' => 60, 'page' => 1],
        CorporatePlan::class,
    ],
    'update' => [
        fn() => Autentique::corporate()->updateSubscriptionPlan('p1', new NewSubscriptionPlan('Gold', 200, 50)),
        Operation::CorporateUpdateSubscriptionPlan,
        ['id' => 'p1'],
        ['id' => 'p1', 'plan' => ['display_name' => 'Gold', 'document_amount' => 200, 'credit_amount' => 50]],
        CorporatePlan::class,
    ],
    'assign' => [
        fn() => Autentique::corporate()->assignSubscriptionPlan(123, 'p1', CarbonImmutable::parse('2026-12-01')),
        Operation::CorporateUpdateOrganizationSubscription,
        ['name' => 'Gold', 'documents' => 200, 'credits' => 50, 'credits_bonus' => 5, 'renew_at' => '2026-12-01T00:00:00.000000Z'],
        ['organization_id' => 123, 'plan_id' => 'p1', 'renew_date' => '2026-12-01'],
        Subscription::class,
    ],
]);

it('refuses a plan with nothing in it', function () {
    new NewSubscriptionPlan('Empty', 0, 10);
})->throws(InvalidInput::class);

it('refuses an organization update that changes nothing', function () {
    new ChildOrganizationChanges();
})->throws(InvalidInput::class);

it('sends the Corporate signer options with createDocument', function () {
    $signer = Signer::email('ana@example.com')
        ->withCpf('000.000.000-00')
        ->ephemeralSession()
        ->withPrefilledFields(new PrefilledFields(name: 'Ana', birthdate: CarbonImmutable::parse('1996-01-23'), language: Language::PortugueseBrazil))
        ->qualified();

    expect($signer->toArray())->toBe([
        'email' => 'ana@example.com',
        'action' => 'SIGN',
        'configs' => [
            'cpf' => '000.000.000-00',
            'session_behavior' => 'EPHEMERAL_SESSION',
            'prefilled_fields' => ['name' => 'Ana', 'birthdate' => '1996-01-23', 'language' => 'pt-BR'],
        ],
        'type' => 'QUALIFIED',
    ]);
});

it('answers every Corporate operation in the fake', function () {
    $fake = Autentique::fake();
    $corporate = Autentique::corporate();

    expect($corporate->organizations()[0]->name)->toBe('Fake child organization')
        ->and($corporate->createOrganization(new NewChildOrganization(name: 'Branch'))->name)->toBe('Branch')
        ->and($corporate->members(1)[0]->apiToken)->toStartWith('fake-member-token')
        ->and($corporate->addMember(1, new NewMember(name: 'João'))->name)->toBe('João')
        ->and($corporate->loginCode(1, 'u1'))->toStartWith('fake-login-code')
        ->and($corporate->createWebhookEndpoint(1, 'https://x.test', 'x', WebhookEndpointType::Document, [WebhookEventType::DocumentFinished])->secret)->toStartWith('fake-secret')
        ->and($corporate->plans([7])[0]->organizationId)->toBe(7)
        ->and($corporate->apiUsage(1, now(), now())->days)->toBe([])
        ->and($corporate->createSubscriptionPlan(new NewSubscriptionPlan('Gold', 1, 1))->displayName)->toBe('Gold')
        ->and($corporate->assignSubscriptionPlan(1, 'p1')->name)->toBe('Fake plan')
        ->and($corporate->changePlan(5, CustomPlan::Free)->id)->toBe(5)
        ->and($corporate->deleteOrganization(5))->toBeTrue();

    $fake->assertSent(Operation::CorporateCreateOrganization);
});
