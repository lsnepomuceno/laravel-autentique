<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Autentique's error codes, in Brazilian Portuguese
|--------------------------------------------------------------------------
|
| Taken from the Portuguese table in Autentique's documentation, where it has
| one. See lang/en/errors.php for how the rest were written.
|
*/

return [
    'unauthorized' => 'Você não está mais autenticado.',
    'document_not_found' => 'Documento não encontrado.',
    'folder_not_found' => 'Pasta não encontrada.',
    'signature_not_found' => 'A assinatura não foi encontrada, ou o dono do token não é signatário deste documento.',
    'document_signed' => 'O documento já foi assinado.',
    'not_your_turn' => 'Não é a sua vez de assinar o documento.',
    'must_be_a_string' => 'É somente permitido texto.',
    'must_be_an_array' => 'Não é uma lista.',
    'not_a_valid_date' => 'Não é uma data válida.',
    'must_be_a_valid_email_address' => 'Não é um email válido.',
    'must_be_a_file' => 'Não é um arquivo.',
    'failed_to_upload' => 'Erro ao enviar o arquivo.',
    'could_not_upload_file' => 'Não foi possível enviar o arquivo.',
    'field_required' => 'Este campo é obrigatório.',
    'unavailable_credits' => 'Os seus documentos acabaram, você já criou todos os documentos disponíveis no seu plano.',
    'unavailable_verifications_credits' => 'Créditos de verificação adicional insuficientes.',
    'may_not_be_greater_than' => 'Não pode ter mais que :parameter caracteres.',
    'must_be_at_least' => 'Não pode ter menos que :parameter caracteres.',
    'must_be_at_least_characters' => 'Não pode ter menos que :parameter caracteres.',
    'format_is_invalid' => 'O formato do campo está incorreto.',
    'invalid_date' => 'Não é uma data válida.',
    'without_permission' => 'Você precisa ser um administrador da organização para executar esta ação.',
    'must_be_a_valid_file' => 'Somente são permitidos arquivos com as extensões :parameter.',
    'not_a_member_of_organization' => 'Você precisa ser um membro da mesma organização para executar esta ação.',
    'too_many_resent_emails' => 'Todas estas assinaturas foram reenviadas recentemente. Aguarde antes de reenviar.',
    'sms_delivery_not_allowed_on_foreign_documents' => 'O envio por SMS não está disponível para documentos fora do Brasil.',
];
