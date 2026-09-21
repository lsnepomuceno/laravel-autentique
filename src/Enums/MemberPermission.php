<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * What a member of a child organization may do. Names ending in `Group` reach
 * the member's group; names ending in `Organization`, the whole organization.
 */
enum MemberPermission: string
{
    case CreateDocuments = 'create_documents';
    case ArchiveDocuments = 'archive_documents';
    case DeleteDocuments = 'delete_documents';
    case SignDocuments = 'sign_documents';
    case ViewDocumentsGroup = 'view_documents_gr';
    case ViewFoldersGroup = 'view_folders_gr';
    case ActionsDocumentsGroup = 'actions_documents_gr';
    case ActionsFoldersGroup = 'actions_folders_gr';
    case ActionsTemplatesGroup = 'actions_templates_gr';
    case ViewDocumentsOrganization = 'view_documents_oz';
    case ViewFoldersOrganization = 'view_folders_oz';
    case ViewMemberDocumentsOrganization = 'view_member_documents_oz';
    case ViewMemberFoldersOrganization = 'view_member_folders_oz';
    case ViewGroupoupDocumentsOrganization = 'view_group_documents_oz';
    case ViewGroupoupFoldersOrganization = 'view_group_folders_oz';
    case ActionsDocumentsOrganization = 'actions_documents_oz';
    case ActionsFoldersOrganization = 'actions_folders_oz';
    case ViewInvoicesOrganization = 'view_invoices_oz';
    case ChangePlanOrganization = 'change_plan_oz';
    case ActionsMembersOrganization = 'actions_members_oz';
    case ActionsGroupoupsOrganization = 'actions_groups_oz';
    case ActionsWebhooksOrganization = 'actions_webhooks_oz';
    case ChangeAppearancesOrganization = 'change_appearances_oz';
}
