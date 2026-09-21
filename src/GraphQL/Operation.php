<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\GraphQL;

/**
 * Every GraphQL operation the package sends, one case per `.graphql` file.
 *
 * The value is the file's path under `src/Resources/graphql/`, without the
 * extension, so code asks for `Operation::CreateDocument` and never for a path
 * or a method name (docs/decisions/0003-operations-live-in-graphql-files.md).
 * The operation's name inside the file is the file's name, which the suite
 * checks for every case.
 */
enum Operation: string
{
    // Queries, standard endpoint.
    case Me = 'queries/me';
    case Document = 'queries/document';
    case Documents = 'queries/documents';
    case DocumentsByFolder = 'queries/documentsByFolder';
    case Organization = 'queries/organization';
    case Organizations = 'queries/organizations';
    case Folders = 'queries/folders';
    case Folder = 'queries/folder';
    case EmailTemplates = 'queries/emailTemplates';

    /** The schema itself, for `autentique:schema`. */
    case Introspection = 'queries/introspection';

    // Mutations, standard endpoint.
    case CreateDocument = 'mutations/createDocument';
    case UpdateDocument = 'mutations/updateDocument';
    case DeleteDocument = 'mutations/deleteDocument';
    case SignDocument = 'mutations/signDocument';
    case TransferDocument = 'mutations/transferDocument';
    case MoveDocumentToFolder = 'mutations/moveDocumentToFolder';
    case CreateSigner = 'mutations/createSigner';
    case DeleteSigner = 'mutations/deleteSigner';
    case ResendSignatures = 'mutations/resendSignatures';
    case CreateLinkToSignature = 'mutations/createLinkToSignature';
    case ApproveBiometric = 'mutations/approveBiometric';
    case RejectBiometric = 'mutations/rejectBiometric';
    case CreateFolder = 'mutations/createFolder';
    case UpdateFolder = 'mutations/updateFolder';
    case DeleteFolder = 'mutations/deleteFolder';
    case ShareFolder = 'mutations/shareFolder';
    case UpdateSharing = 'mutations/updateSharing';

    // Corporate endpoint, Corporate plan only.
    case CorporateOrganizations = 'corporate/queries/organizations';
    case CorporateOrganizationMembers = 'corporate/queries/organizationMembers';
    case CorporateOrganizationsPlans = 'corporate/queries/organizationsPlans';
    case CorporateApiUsage = 'corporate/queries/childOrganizationsApiUsage';
    case CorporateSubscriptionPlans = 'corporate/queries/subscriptionPlans';
    case CorporateCreateOrganization = 'corporate/mutations/createOrganization';
    case CorporateUpdateOrganization = 'corporate/mutations/updateOrganization';
    case CorporateUpdateOrganizationPlan = 'corporate/mutations/updateOrganizationPlan';
    case CorporateDeleteOrganization = 'corporate/mutations/deleteOrganization';
    case CorporateCreateMember = 'corporate/mutations/createOrganizationMember';
    case CorporateUpdateMember = 'corporate/mutations/updateOrganizationMember';
    case CorporateDeleteMember = 'corporate/mutations/deleteOrganizationMember';
    case CorporateCreateLoginCode = 'corporate/mutations/createOrganizationMemberLoginCode';
    case CorporateCreateEndpoint = 'corporate/mutations/createEndpoint';
    case CorporateCreateSubscriptionPlan = 'corporate/mutations/createSubscriptionPlan';
    case CorporateUpdateSubscriptionPlan = 'corporate/mutations/updateSubscriptionPlan';
    case CorporateUpdateOrganizationSubscription = 'corporate/mutations/updateOrganizationSubscription';

    /**
     * The operation's name, which is the file's name and the name written after
     * `query` or `mutation` inside it.
     */
    public function operationName(): string
    {
        return basename($this->value);
    }

    /**
     * The field of `data` the answer arrives under: the operation's own name,
     * except for introspection, which GraphQL answers under `__schema`.
     */
    public function field(): string
    {
        return $this === self::Introspection ? '__schema' : $this->operationName();
    }

    public function isMutation(): bool
    {
        return str_contains($this->value, 'mutations/');
    }

    /**
     * Which endpoint the operation is sent to.
     */
    public function endpoint(): Endpoint
    {
        return str_starts_with($this->value, 'corporate/') ? Endpoint::Corporate : Endpoint::Standard;
    }

    /**
     * Whether the request carries a file, and so has to be a GraphQL multipart
     * request rather than JSON.
     */
    public function uploadsAFile(): bool
    {
        return $this === self::CreateDocument;
    }
}
