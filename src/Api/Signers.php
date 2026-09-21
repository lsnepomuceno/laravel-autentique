<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Api;

use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Data\Input\Signer;
use LSNepomuceno\LaravelAutentique\Data\{Link, Signature};
use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException, GraphQLError, InvalidInput, ResendThrottled};
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;

/**
 * The signers of an existing document, each identified by the `$publicId` of
 * their `Data\Signature`.
 */
final readonly class Signers
{
    public function __construct(private GraphQLClient $client) {}

    /**
     * Adds a signer to a document that can still be changed, and sends them
     * the request as `Signer` says.
     *
     * @throws AutentiqueException
     */
    public function add(string $documentId, Signer $signer): Signature
    {
        return $this->signature(Operation::CreateSigner, ['document_id' => $documentId, 'signer' => $signer->toArray()]);
    }

    /**
     * Removes a signer who has not acted yet. It cannot be undone.
     *
     * @throws AutentiqueException
     */
    public function remove(string $publicId, string $documentId): bool
    {
        return $this->confirm(Operation::DeleteSigner, ['public_id' => $publicId, 'document_id' => $documentId]);
    }

    /**
     * Sends the request to sign again. Free of charge.
     *
     * Signatures resent recently are skipped; when every one was,
     * `ResendThrottled` says so and nothing is sent.
     *
     * @param  list<string>  $publicIds
     *
     * @throws InvalidInput
     * @throws ResendThrottled
     * @throws AutentiqueException
     */
    public function resend(array $publicIds): bool
    {
        if ($publicIds === []) {
            throw new InvalidInput('Resending needs at least one signature.');
        }

        try {
            return $this->confirm(Operation::ResendSignatures, ['public_ids' => array_values($publicIds)]);
        } catch (GraphQLError $error) {
            foreach ($error->errors as $apiError) {
                if ($apiError->code === ErrorCode::TooManyResentEmails) {
                    throw ResendThrottled::from($error);
                }
            }

            throw $error;
        }
    }

    /**
     * A signing link exclusive to this signer, to deliver by another channel.
     *
     * @throws AutentiqueException
     */
    public function link(string $publicId): Link
    {
        $data = Payload::of($this->client->send(Operation::CreateLinkToSignature, ['public_id' => $publicId]));

        return Link::fromPayload($data->object(Operation::CreateLinkToSignature->field()) ?? Payload::of([]));
    }

    /**
     * Approves a manual identity check: the photos a signer sent, which
     * Autentique holds for you to decide on.
     *
     * @param  int  $verificationId  `Data\Verification::$id`.
     *
     * @throws AutentiqueException
     */
    public function approveBiometric(int $verificationId, string $publicId): Signature
    {
        return $this->signature(Operation::ApproveBiometric, ['verification_id' => $verificationId, 'public_id' => $publicId]);
    }

    /**
     * Rejects a manual identity check.
     *
     * @param  int  $verificationId  `Data\Verification::$id`.
     *
     * @throws AutentiqueException
     */
    public function rejectBiometric(int $verificationId, string $publicId): Signature
    {
        return $this->signature(Operation::RejectBiometric, ['verification_id' => $verificationId, 'public_id' => $publicId]);
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function signature(Operation $operation, array $variables): Signature
    {
        $data = Payload::of($this->client->send($operation, $variables));

        return Signature::fromPayload($data->object($operation->field()) ?? Payload::of([]));
    }

    /**
     * @param  array<string, mixed>  $variables
     *
     * @throws AutentiqueException
     */
    private function confirm(Operation $operation, array $variables): bool
    {
        return Payload::of($this->client->send($operation, $variables))->bool($operation->field());
    }
}
