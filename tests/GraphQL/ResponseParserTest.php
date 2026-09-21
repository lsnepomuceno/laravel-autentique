<?php

declare(strict_types=1);

use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;
use LSNepomuceno\LaravelAutentique\Exceptions\{AutentiqueException,
    GraphQLError,
    NotFound,
    RateLimited,
    RequestFailed,
    TransportFailed,
    Unauthenticated,
    ValidationFailed};
use LSNepomuceno\LaravelAutentique\GraphQL\ResponseParser;

/**
 * Every shape of failure the documentation shows, each turned into the
 * exception that names it (docs/decisions/0005-exceptions-name-the-real-fault.md).
 */

/**
 * The exception a parse throws, so its properties can be asserted.
 */
function parseFailure(int $status, mixed $body, ?string $retryAfter = null): RequestFailed
{
    try {
        new ResponseParser()->parse($status, $body, 'someOperation', 'req-123', $retryAfter);
    } catch (RequestFailed $exception) {
        return $exception;
    }

    throw new RuntimeException('The parse did not fail.');
}

it('returns data when there is no error', function () {
    expect(new ResponseParser()->parse(200, ['data' => ['me' => ['id' => 'abc']]]))
        ->toBe(['me' => ['id' => 'abc']]);
});

it('turns a validation error into ValidationFailed, with its codes per field', function () {
    $exception = parseFailure(200, responseFixture('errors/validation'));

    expect($exception)->toBeInstanceOf(ValidationFailed::class)
        ->and($exception->getMessage())->toBe('Autentique refused the request: folder.name: must_be_at_least_characters:3.')
        ->and($exception->operation)->toBe('someOperation')
        ->and($exception->requestId)->toBe('req-123')
        ->and($exception->status)->toBe(200);

    /** @var ValidationFailed $exception */
    $violation = $exception->violations()[0];

    expect($violation->field)->toBe('folder.name')
        ->and($violation->code)->toBe(ErrorCode::MustBeAtLeastCharacters)
        ->and($violation->parameter)->toBe('3')
        ->and($exception->messages())->toBe(['folder.name' => ["Can't have less than 3 characters."]]);
});

it('turns a missing resource into NotFound', function () {
    $exception = parseFailure(200, responseFixture('errors/not-found'));

    expect($exception)->toBeInstanceOf(NotFound::class)
        ->and($exception->getMessage())->toBe('Folder not found.')
        ->and($exception->errors[0]->path)->toBe(['folder']);
});

it('treats partial data beside an error as a failure', function () {
    // data.folder is null and present: the error still wins.
    expect(parseFailure(200, responseFixture('errors/not-found')))->toBeInstanceOf(NotFound::class);
});

it('turns a query the schema refuses into GraphQLError, keeping the message', function () {
    $exception = parseFailure(200, responseFixture('errors/invalid-variable'));

    expect($exception)->toBeInstanceOf(GraphQLError::class)
        ->and($exception->getMessage())->toContain('Variable "$folder" got invalid value null');
});

it('turns a documented code into GraphQLError carrying the code', function () {
    $exception = parseFailure(200, responseFixture('errors/coded'));

    expect($exception)->toBeInstanceOf(GraphQLError::class)
        ->and($exception->errors[0]->code)->toBe(ErrorCode::TooManyResentEmails);
});

it('turns a missing OAuth scope into Unauthenticated, although it arrives with HTTP 200', function () {
    expect(parseFailure(200, responseFixture('errors/unauthorized-scope')))->toBeInstanceOf(Unauthenticated::class);
});

it('turns HTTP 401 into Unauthenticated', function () {
    expect(parseFailure(401, ['message' => 'unauthorized']))->toBeInstanceOf(Unauthenticated::class);
});

it('turns HTTP 429 into RateLimited, with the wait Autentique asked for', function () {
    $exception = parseFailure(429, ['message' => 'Too Many Attempts.'], '30');

    expect($exception)->toBeInstanceOf(RateLimited::class);

    /** @var RateLimited $exception */
    expect($exception->retryAfter)->toBe(30);
});

it('turns a body that is not GraphQL into TransportFailed', function (int $status, mixed $body) {
    expect(parseFailure($status, $body))->toBeInstanceOf(TransportFailed::class);
})->with([
    'a proxy error page' => [502, null],
    'a server error with JSON' => [500, ['message' => 'Server Error']],
    'a string' => [200, 'ok'],
]);

it('turns an answer with neither data nor errors into GraphQLError', function () {
    expect(parseFailure(200, ['data' => null]))->toBeInstanceOf(GraphQLError::class);
});

it('reads errors written as a single object', function () {
    expect(parseFailure(200, ['errors' => ['message' => 'Document not found']]))->toBeInstanceOf(NotFound::class);
});

it('keeps every error, not only the first', function () {
    $exception = parseFailure(200, ['errors' => [['message' => 'first'], ['message' => 'second']]]);

    expect($exception->errors)->toHaveCount(2)
        ->and($exception->getMessage())->toBe('first. second.');
});

it('makes every failure catchable in one place', function () {
    expect(parseFailure(401, null))->toBeInstanceOf(AutentiqueException::class);
});
