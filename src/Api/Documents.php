<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use LSNepomuceno\LaravelAutentique\Contracts\{FileSource, GraphQLClient};
use LSNepomuceno\LaravelAutentique\Data\Document;
use LSNepomuceno\LaravelAutentique\Data\Input\{NewDocument, Signer};
use LSNepomuceno\LaravelAutentique\Enums\DocumentType;
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, InvalidInput};
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * Documents: creating them, and everything done to them afterwards.
 */
final readonly class Documents
{
    public function __construct(
        private GraphQLClient $client,
        private Repository $config,
    ) {}

    /**
     * Creates a document from a file and sends it to its signers.
     *
     * A fluent way to build the same call is `Autentique::newDocument()`.
     *
     * @param  list<Signer>  $signers
     * @param  ?bool  $sandbox  Null uses `autentique.sandbox`
     *                          (docs/decisions/0006-sandbox-is-chosen-per-call.md).
     * @param  ?string  $folderId  Files the document in a folder as it is created.
     * @param  ?int  $organizationId  Creates it in another of the account's organizations.
     *
     * @throws InvalidInput
     * @throws AutentiqueException
     */
    public function create(
        NewDocument $document,
        array $signers,
        FileSource $file,
        ?bool $sandbox = null,
        ?string $folderId = null,
        ?int $organizationId = null,
        DocumentType $type = DocumentType::Default,
    ): Document {
        if ($signers === []) {
            throw new InvalidInput('A document needs at least one signer.');
        }

        $markdown = Str::lower(pathinfo($file->name(), PATHINFO_EXTENSION)) === 'md';

        if ($type === DocumentType::WhatsappFlow && ! $markdown) {
            throw new InvalidInput('A WhatsApp Flow document is a Markdown file, and '
                . $file->name() . ' is not one.');
        }

        if ($type !== DocumentType::WhatsappFlow && $markdown) {
            throw new InvalidInput('A Markdown file is only accepted as a WhatsApp Flow document.');
        }

        $variables = array_filter([
            'document' => $document->toArray(),
            'signers' => array_map(fn(Signer $signer): array => $signer->toArray(), $signers),
            'sandbox' => $sandbox ?? $this->config->get('autentique.sandbox') === true,
            'folder_id' => $folderId,
            'organization_id' => $organizationId,
            'type' => $type === DocumentType::Default ? null : $type->value,
        ], fn(mixed $value): bool => $value !== null);

        return $this->document(Operation::CreateDocument, $variables, $file);
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function document(Operation $operation, array $variables, ?FileSource $file = null): Document
    {
        $data = Payload::of($this->client->send($operation, $variables, $file));

        return Document::fromPayload($data->object($operation->field()) ?? Payload::of([]));
    }
}
