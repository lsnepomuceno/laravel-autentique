<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Testing;

use Carbon\CarbonImmutable;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * What the fake answers when a test does not say: data shaped as Autentique
 * would shape it, built from what was sent, so a created document carries the
 * name and the signers the application gave it.
 *
 * The match has no default, so an operation added to the enum without an
 * answer here fails static analysis rather than a consumer's test.
 */
final class FakeAnswers
{
    private int $sequence = 0;

    /**
     * The value under the operation's field.
     *
     * @param  array<string, mixed>  $variables
     */
    public function for(Operation $operation, array $variables): mixed
    {
        $sent = new Payload($variables);

        return match ($operation) {
            Operation::Me => $this->user(),
            Operation::Document, Operation::UpdateDocument => $this->document($sent->nullableString('id') ?? $this->id('document'), $sent->nullableString('document.name') ?? 'Fake document', []),
            Operation::CreateDocument => $this->document($this->id('document'), $sent->nullableString('document.name') ?? 'Fake document', $sent->list('signers'), $sent->nullableBool('sandbox')),
            Operation::Documents, Operation::DocumentsByFolder, Operation::Folders, Operation::EmailTemplates => $this->emptyPage($sent),
            Operation::DeleteDocument, Operation::SignDocument, Operation::TransferDocument, Operation::MoveDocumentToFolder,
            Operation::DeleteSigner, Operation::ResendSignatures, Operation::DeleteFolder => true,
            Operation::CreateSigner => $this->signature($sent->object('signer') ?? new Payload([])),
            Operation::ApproveBiometric, Operation::RejectBiometric => [...$this->signature(new Payload([])), 'public_id' => $sent->nullableString('public_id')],
            Operation::CreateLinkToSignature => ['id' => $this->id('link'), 'short_link' => 'https://assina.ae/' . $this->id('link')],
            Operation::Organization => $this->organization(),
            Operation::Organizations => [$this->organization()],
            Operation::Folder, Operation::CreateFolder, Operation::UpdateFolder, Operation::ShareFolder, Operation::UpdateSharing => $this->folder(
                $sent->nullableString('id') ?? $sent->nullableString('folder_id') ?? $this->id('folder'),
                $sent->nullableString('folder.name') ?? 'Fake folder',
            ),
            Operation::Introspection => ['queryType' => ['name' => 'Query'], 'mutationType' => ['name' => 'Mutation'], 'subscriptionType' => null, 'types' => [], 'directives' => []],
            Operation::CorporateOrganizations => [$this->childOrganization(null)],
            Operation::CorporateCreateOrganization => $this->childOrganization($sent->nullableString('organization.name')),
            Operation::CorporateUpdateOrganization, Operation::CorporateUpdateOrganizationPlan => [
                ...$this->childOrganization($sent->nullableString('organization.name')),
                'id' => $sent->nullableInt('id') ?? $sent->nullableInt('organization_id') ?? 2,
            ],
            Operation::CorporateDeleteOrganization, Operation::CorporateDeleteMember => true,
            Operation::CorporateOrganizationMembers => [$this->member(null)],
            Operation::CorporateCreateMember, Operation::CorporateUpdateMember => $this->member($sent->object('member')),
            Operation::CorporateOrganizationsPlans => array_map(
                fn(mixed $id): array => ['organization_id' => $id, 'subscription' => ['name' => 'Fake plan']],
                array_values($sent->object('organizations_ids')?->all() ?? []),
            ),
            Operation::CorporateApiUsage => ['pricing' => [], 'usage' => []],
            Operation::CorporateSubscriptionPlans => [],
            Operation::CorporateCreateSubscriptionPlan, Operation::CorporateUpdateSubscriptionPlan => [
                ...($sent->object('plan')?->all() ?? []),
                'id' => $sent->nullableString('id') ?? $this->id('plan'),
            ],
            Operation::CorporateUpdateOrganizationSubscription => ['name' => 'Fake plan', 'documents' => 100, 'credits' => 100],
            Operation::CorporateCreateLoginCode => $this->id('login-code'),
            Operation::CorporateCreateEndpoint => [
                'secret' => $this->id('secret'),
                'webhook_endpoint' => [
                    'id' => $this->id('endpoint'),
                    'url' => $sent->nullableString('url'),
                    'active' => true,
                    'events' => $sent->strings('events'),
                ],
            ],
        };
    }

    private function id(string $kind): string
    {
        return 'fake-' . $kind . '-' . ++$this->sequence;
    }

    /**
     * @return array<string, mixed>
     */
    private function user(): array
    {
        return [
            'id' => 'fake-user',
            'name' => 'Fake User',
            'email' => 'fake@example.com',
            'subscription' => ['has_premium_features' => true, 'documents' => 100, 'credits' => 100],
            'organization' => $this->organization(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function organization(): array
    {
        return ['id' => 1, 'uuid' => 'fake-organization', 'name' => 'Fake organization', 'cnpj' => null, 'groups' => []];
    }

    /**
     * @param  list<Payload>  $signers
     * @return array<string, mixed>
     */
    private function document(string $id, string $name, array $signers, ?bool $sandbox = null): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'sandbox' => $sandbox,
            'created_at' => CarbonImmutable::now()->toIso8601ZuluString('microsecond'),
            'files' => ['original' => "https://fake.autentique.test/{$id}/original.pdf", 'signed' => null],
            'signatures' => array_map($this->signature(...), $signers),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function signature(Payload $signer): array
    {
        $id = $this->id('signature');
        $link = $signer->nullableString('delivery_method') === 'DELIVERY_METHOD_LINK' || ($signer->has('name') && ! $signer->has('email') && ! $signer->has('phone'));

        return [
            'public_id' => $id,
            'name' => $signer->nullableString('name'),
            'email' => $signer->nullableString('email'),
            'delivery_method' => $signer->nullableString('delivery_method') ?? ($signer->has('email') ? 'DELIVERY_METHOD_EMAIL' : null),
            'created_at' => CarbonImmutable::now()->toIso8601ZuluString('microsecond'),
            'action' => ['name' => $signer->nullableString('action') ?? 'SIGN'],
            'link' => $link ? ['id' => $id, 'short_link' => "https://assina.ae/{$id}"] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function folder(string $id, string $name): array
    {
        $now = CarbonImmutable::now()->toIso8601ZuluString('microsecond');

        return ['id' => $id, 'name' => $name, 'path' => "/{$name}", 'context' => 'USER', 'created_at' => $now, 'updated_at' => $now];
    }

    /**
     * @return array<string, mixed>
     */
    private function childOrganization(?string $name): array
    {
        return ['id' => 2, 'uuid' => 'fake-child-organization', 'name' => $name ?? 'Fake child organization', 'plan' => 'FREE', 'groups' => []];
    }

    /**
     * @return array<string, mixed>
     */
    private function member(?Payload $member): array
    {
        return [
            'id' => $this->id('member'),
            'user' => ['id' => $this->id('user'), 'name' => $member?->nullableString('name') ?? 'Fake member', 'email' => $member?->nullableString('email')],
            'api_token' => ['access_token' => $this->id('member-token')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPage(Payload $sent): array
    {
        return [
            'data' => [],
            'total' => 0,
            'per_page' => $sent->nullableInt('limit') ?? 20,
            'current_page' => $sent->nullableInt('page') ?? 1,
            'last_page' => 1,
            'has_more_pages' => false,
        ];
    }
}
