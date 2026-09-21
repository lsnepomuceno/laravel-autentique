# Corporate

::: warning Experimental
The Corporate area has not yet run against Autentique itself, and until it has it may change
in a minor release. [The public API](/spec/public-api#experimental) says what
that covers.
:::

The Corporate plan extends the API with a second endpoint, for accounts that
manage organizations of their own: child organizations, their members, their
plans, their webhook endpoints and what their use of the API costs. The package
sends it with the same token and the same client
([0010](/decisions/0010-corporate-is-an-area-on-its-own-endpoint)).

```php
$corporate = Autentique::corporate();
```

Every call fails, with the error Autentique returns, for an account without the
Corporate plan.

## Child organizations

```php
use LSNepomuceno\LaravelAutentique\Data\Input\{ChildOrganizationChanges, NewChildOrganization};

$child = $corporate->createOrganization(new NewChildOrganization(
    name: 'Branch office',
    locale: new Locale('BR', Language::PortugueseBrazil),
));

$corporate->organizations(name: 'Branch');                        // list<ChildOrganization>
$corporate->updateOrganization($child->id, new ChildOrganizationChanges(name: 'Branch office, south'));
$corporate->changePlan($child->id, CustomPlan::Professional, daysUntilExpiration: 30);
$corporate->plans([$child->id]);                                   // the plan each is on
$corporate->deleteOrganization($child->id);                        // once it has no members
```

A new child organization gets the groups `Administrador` and `Sem grupo`.

## Members

```php
use LSNepomuceno\LaravelAutentique\Data\Input\NewMember;
use LSNepomuceno\LaravelAutentique\Enums\MemberPermission;

$member = $corporate->addMember($child->id, new NewMember(
    name: 'João',
    email: 'joao@example.com',
    birthday: CarbonImmutable::parse('1995-11-24'),
    permissions: [MemberPermission::CreateDocuments, MemberPermission::SignDocuments],
));

$corporate->members($child->id);
$corporate->updateMember($child->id, $member->userId, new NewMember(groupId: 1));
$corporate->removeMember($child->id, $member->userId);
```

Without a group, the member lands in `Sem grupo` and each permission listed is
granted.

**`$member->apiToken` acts as that member.** It is what lets a Corporate account
work inside its child organizations, and it belongs in the same places as your
own token and nowhere else.

## The dashboard in an iframe

```php
$code = $corporate->loginCode($child->id, $member->userId);   // valid for five minutes
```

The code is posted to an iframe of the dashboard, as Autentique's Corporate
documentation shows.

## Webhook endpoints

```php
$endpoint = $corporate->createWebhookEndpoint(
    $child->id,
    'https://your-app.example/webhooks/autentique',
    'Signatures',
    WebhookEndpointType::Signature,
    [WebhookEventType::SignatureAccepted, WebhookEventType::SignatureRejected],
);

$endpoint->secret;   // shown only here: store it where autentique.webhooks.secret is read from
```

An endpoint listens to one kind of resource, and only that resource's events
reach it; asking for others is refused before sending. `signature.biometric_reset`
and `signature.delivery_failed` are delivered by Autentique but cannot be
registered through the API, and are refused too.

## Custom plans

```php
use LSNepomuceno\LaravelAutentique\Data\Input\NewSubscriptionPlan;

$plan = $corporate->createSubscriptionPlan(new NewSubscriptionPlan(
    displayName: 'Gold',
    documentAmount: 100,
    creditAmount: 50,
    intervalType: IntervalType::Month,
    creditsRecurrence: CreditsRecurrence::Detached,
));

$corporate->subscriptionPlans();
$corporate->updateSubscriptionPlan($plan->id, new NewSubscriptionPlan('Gold', 200, 50));
$corporate->assignSubscriptionPlan($child->id, $plan->id, renewAt: CarbonImmutable::parse('2026-12-01'));
```

## API usage

```php
$usage = $corporate->apiUsage($child->id, from: now()->startOfMonth(), until: now());

$usage->pricing[0]['prices']->createDocument;   // the price in force
$usage->days[0]['used']->email;                 // what was used that day
```

## Signer options

Three options of `createDocument` need the Corporate plan, and are methods on
`Signer`:

```php
Signer::email('ana@example.com')
    ->qualified()                                       // a qualified certificate for this signer only
    ->ephemeralSession()                                // signed out when done, or after five minutes
    ->withPrefilledFields(new PrefilledFields(name: 'Ana', cpf: '000.000.000-00'));
```

The package cannot know the account's plan, so it sends them as asked;
Autentique refuses them without Corporate.
