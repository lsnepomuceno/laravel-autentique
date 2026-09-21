<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\GraphQL;

use LSNepomuceno\LaravelAutentique\Data\{ApiError, Violation};
use LSNepomuceno\LaravelAutentique\Exceptions\{GraphQLError,
    InsufficientScope,
    NotFound,
    RateLimited,
    RequestFailed,
    TransportFailed,
    Unauthenticated,
    ValidationFailed};

/**
 * Turns what Autentique answered into `data`, or into the exception that names
 * what went wrong (docs/decisions/0005-exceptions-name-the-real-fault.md).
 *
 * **GraphQL errors arrive with HTTP 200.** Only a rejected token (401) and a
 * rate limit (429) are HTTP statuses; everything else is an `errors` array
 * beside `data`, and an `errors` array is a failure even when `data` is partly
 * filled.
 *
 * It works on what the client read from the response rather than on the
 * response itself, so it holds no reference to the HTTP layer
 * (docs/spec/invariants.md, rule 1).
 */
final class ResponseParser
{
    /**
     * @return array<string, mixed> The response's `data`.
     *
     * @throws RequestFailed
     */
    public function parse(
        int $status,
        mixed $body,
        ?string $operation = null,
        ?string $requestId = null,
        ?string $retryAfter = null,
    ): array {
        if ($status === 401) {
            throw new Unauthenticated('Autentique rejected the token.', $operation, $status, $requestId);
        }

        if ($status === 429) {
            throw new RateLimited(
                'Autentique refused the request: too many attempts.',
                is_numeric($retryAfter) ? (int) $retryAfter : null,
                $operation,
                $requestId,
            );
        }

        if (! is_array($body)) {
            throw new TransportFailed(
                "Autentique answered HTTP {$status} with a body that is not a GraphQL response.",
                $operation,
                $status,
                $requestId,
            );
        }

        $errors = $this->errors($body['errors'] ?? null);

        if ($errors !== []) {
            throw $this->exceptionFor($errors, $status, $operation, $requestId);
        }

        if ($status < 200 || $status >= 300) {
            throw new TransportFailed("Autentique answered HTTP {$status}.", $operation, $status, $requestId);
        }

        $data = $body['data'] ?? null;

        if (! is_array($data)) {
            throw new GraphQLError('Autentique answered with no data and no error.', $operation, $status, $requestId);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @return list<ApiError>
     */
    private function errors(mixed $errors): array
    {
        if (! is_array($errors) || $errors === []) {
            return [];
        }

        // The documentation once shows `errors` as an object. A single error
        // written that way is still an error.
        if (! array_is_list($errors)) {
            $errors = [$errors];
        }

        return array_values(array_map(
            fn(mixed $error): ApiError => ApiError::fromArray(is_array($error) ? $error : ['message' => $error]),
            $errors,
        ));
    }

    /**
     * The narrowest exception every error agrees on, checked from the most
     * specific fault to the least.
     *
     * @param  non-empty-list<ApiError>  $errors
     */
    private function exceptionFor(array $errors, int $status, ?string $operation, ?string $requestId): RequestFailed
    {
        $summary = implode(' ', array_map(fn(ApiError $error): string => $this->describe($error), $errors));

        // The OAuth documentation shows a missing scope as `Unauthorized`,
        // capitalised, with HTTP 200; the error table's `unauthorized`, lower
        // case, is a token that is no longer valid
        // (docs/decisions/0011-oauth-goes-through-the-same-client.md).
        foreach ($errors as $error) {
            if ($error->message === 'Unauthorized') {
                return new InsufficientScope("The token lacks the scope this operation needs: {$summary}", $operation, $status, $requestId, $errors);
            }
        }

        foreach ($errors as $error) {
            if ($error->isUnauthorized()) {
                return new Unauthenticated("Autentique refused the token: {$summary}", $operation, $status, $requestId, $errors);
            }
        }

        foreach ($errors as $error) {
            if ($error->isValidation()) {
                return new ValidationFailed("Autentique refused the request: {$summary}", $operation, $status, $requestId, $errors);
            }
        }

        foreach ($errors as $error) {
            if ($error->isNotFound()) {
                return new NotFound($summary, $operation, $status, $requestId, $errors);
            }
        }

        return new GraphQLError($summary, $operation, $status, $requestId, $errors);
    }

    private function describe(ApiError $error): string
    {
        if ($error->violations === []) {
            return rtrim($error->message, '.') . '.';
        }

        $parts = array_map(
            fn(Violation $violation): string => "{$violation->field}: {$violation->rawCode}"
                . ($violation->parameter === null ? '' : ":{$violation->parameter}"),
            $error->violations,
        );

        return implode(', ', $parts) . '.';
    }
}
