<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Enums;

/**
 * The error and validation codes Autentique documents.
 *
 * A code arrives as a GraphQL error's `message`, or inside
 * `extensions.validation` keyed by field, sometimes with a parameter after a
 * colon: `must_be_at_least_characters:3`. A code the API sends and this enum
 * does not know still reaches the caller, as the raw string, inside the
 * exception (docs/decisions/0005-exceptions-name-the-real-fault.md).
 *
 * The human text lives in `lang/{en,pt_BR}/errors.php`, taken from
 * Autentique's own table, so it follows the application's locale.
 */
enum ErrorCode: string
{
    case Unauthorized = 'unauthorized';
    case DocumentNotFound = 'document_not_found';
    case FolderNotFound = 'folder_not_found';
    case SignatureNotFound = 'signature_not_found';
    case DocumentSigned = 'document_signed';
    case NotYourTurn = 'not_your_turn';
    case MustBeAString = 'must_be_a_string';
    case MustBeAnArray = 'must_be_an_array';
    case NotAValidDate = 'not_a_valid_date';
    case MustBeAValidEmailAddress = 'must_be_a_valid_email_address';
    case MustBeAFile = 'must_be_a_file';
    case FailedToUpload = 'failed_to_upload';
    case CouldNotUploadFile = 'could_not_upload_file';
    case FieldRequired = 'field_required';
    case UnavailableCredits = 'unavailable_credits';
    case UnavailableVerificationsCredits = 'unavailable_verifications_credits';
    case MayNotBeGreaterThan = 'may_not_be_greater_than';
    case MustBeAtLeast = 'must_be_at_least';
    case MustBeAtLeastCharacters = 'must_be_at_least_characters';
    case FormatIsInvalid = 'format_is_invalid';
    case InvalidDate = 'invalid_date';
    case WithoutPermission = 'without_permission';
    case MustBeAValidFile = 'must_be_a_valid_file';
    case NotAMemberOfOrganization = 'not_a_member_of_organization';
    case TooManyResentEmails = 'too_many_resent_emails';
    case SmsDeliveryNotAllowedOnForeignDocuments = 'sms_delivery_not_allowed_on_foreign_documents';

    /**
     * Whether the code says the thing asked for does not exist.
     */
    public function meansNotFound(): bool
    {
        return str_ends_with($this->value, '_not_found');
    }

    /**
     * The code's text in the application's locale, with its parameter in place
     * when the code carries one.
     */
    public function message(?string $parameter = null): string
    {
        $message = __("autentique::errors.{$this->value}", ['parameter' => $parameter ?? '']);

        return is_string($message) ? $message : $this->value;
    }
}
