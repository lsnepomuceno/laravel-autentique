<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Autentique's error codes
|--------------------------------------------------------------------------
|
| Taken from the table in Autentique's documentation, where it has one. A code
| found only on the page of the operation that returns it carries text
| written here, in the same register.
|
| `:parameter` is what follows the colon in a code such as
| `must_be_at_least_characters:3`.
|
*/

return [
    'unauthorized' => 'You are not authenticated anymore.',
    'document_not_found' => 'Document not found.',
    'folder_not_found' => 'Folder not found.',
    'signature_not_found' => 'The signature was not found, or the token owner is not a signer of this document.',
    'document_signed' => 'The document was already signed.',
    'not_your_turn' => "It's not your turn to sign the document.",
    'must_be_a_string' => "It's only allowed text.",
    'must_be_an_array' => "It's not a list.",
    'not_a_valid_date' => "It's not a valid date.",
    'must_be_a_valid_email_address' => 'It is not a valid email.',
    'must_be_a_file' => "It's not a file.",
    'failed_to_upload' => 'Error sending a file.',
    'could_not_upload_file' => 'It was not possible to send a file.',
    'field_required' => 'This field is mandatory.',
    'unavailable_credits' => "You've run out of documents. You've already created all the documents available in your plan.",
    'unavailable_verifications_credits' => 'Insufficient additional verification credits.',
    'may_not_be_greater_than' => "Can't have more than :parameter characters.",
    'must_be_at_least' => "Can't have less than :parameter characters.",
    'must_be_at_least_characters' => "Can't have less than :parameter characters.",
    'format_is_invalid' => 'The field format is incorrect.',
    'invalid_date' => "It's not a valid date.",
    'without_permission' => 'You need to be an organization administrator to perform this action.',
    'must_be_a_valid_file' => 'Only files with the following extensions are allowed: :parameter.',
    'not_a_member_of_organization' => 'You need to be a member of the same organization to perform this action.',
    'too_many_resent_emails' => 'Every one of these signatures was resent recently. Wait before resending again.',
    'sms_delivery_not_allowed_on_foreign_documents' => 'SMS delivery is not available for documents outside Brazil.',
];
