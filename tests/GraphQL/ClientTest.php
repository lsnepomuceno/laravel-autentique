<?php

declare(strict_types=1);

use Illuminate\Http\Client\{ConnectionException, Request};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Exceptions\{MissingToken, RateLimited, TransportFailed, Unauthenticated};
use LSNepomuceno\LaravelAutentique\Facades\Autentique;
use LSNepomuceno\LaravelAutentique\GraphQL\{Client, Operation, OperationLoader};

/**
 * What the client puts on the wire, and what it does with what comes back.
 */

beforeEach(function () {
    Sleep::fake();
    config()->set('autentique.token', 'the-token');
});

it('sends an operation as JSON, with its variables and its name', function () {
    Http::fake(['api.autentique.com.br/*' => Http::response(['data' => ['deleteFolder' => true]])]);

    $data = app(GraphQLClient::class)->send(Operation::DeleteFolder, ['id' => 'folder-1']);

    expect($data)->toBe(['deleteFolder' => true]);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.autentique.com.br/v2/graphql'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer the-token')
            && $request->hasHeader('Accept', 'application/json')
            && $request['query'] === app(OperationLoader::class)->load(Operation::DeleteFolder)
            && $request['variables'] === ['id' => 'folder-1']
            && $request['operationName'] === 'deleteFolder';
    });
});

it('sends empty variables as an object, which GraphQL requires', function () {
    Http::fake(['*' => Http::response(['data' => ['me' => null]])]);

    app(GraphQLClient::class)->send(Operation::Me);

    Http::assertSent(fn(Request $request): bool => str_contains($request->body(), '"variables":{}'));
});

it('uploads a file as a GraphQL multipart request, parts in order', function () {
    Http::fake(['*' => Http::response(['data' => ['createDocument' => ['id' => 'doc-1']]])]);

    app(GraphQLClient::class)->send(
        Operation::CreateDocument,
        ['document' => ['name' => 'Contract'], 'signers' => []],
        memoryFile(),
    );

    $request = lastRequest();
    $parts = multipartParts($request);

    expect($request->isMultipart())->toBeTrue()
        ->and(array_keys($parts))->toBe(['operations', 'map', 'file'])
        ->and(json_decode($parts['operations']['contents'], true))->toMatchArray([
            'operationName' => 'createDocument',
            'variables' => ['document' => ['name' => 'Contract'], 'signers' => [], 'file' => null],
        ])
        ->and(json_decode($parts['map']['contents'], true))->toBe(['file' => ['variables.file']])
        ->and($parts['file']['contents'])->toBe('%PDF-1.7 test')
        ->and($parts['file']['filename'])->toBe('contract.pdf');
});

it('sends a document the package does not ship, through the facade', function () {
    Http::fake(['*' => Http::response(['data' => ['me' => ['name' => 'Ana']]])]);

    $data = Autentique::query('query { me { name } }', ['unused' => 1]);

    expect($data)->toBe(['me' => ['name' => 'Ana']]);

    Http::assertSent(fn(Request $request): bool => $request['query'] === 'query { me { name } }'
        && $request['variables'] === ['unused' => 1]);
});

it('sends another token when asked, and leaves the original alone', function () {
    Http::fake(['*' => Http::response(['data' => ['me' => null]])]);

    $client = app(GraphQLClient::class);
    $client->withToken('someone-else')->send(Operation::Me);
    $client->send(Operation::Me);

    $tokens = Http::recorded()->map(fn(array $pair): array => $pair[0]->header('Authorization'))->all();

    expect($tokens)->toBe([['Bearer someone-else'], ['Bearer the-token']]);
});

it('refuses to send without a token', function () {
    Http::fake();
    config()->set('autentique.token', null);

    app(GraphQLClient::class)->send(Operation::Me);
})->throws(MissingToken::class, 'AUTENTIQUE_TOKEN');

it('retries a refused request, waiting as long as Autentique asks', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '3'])
        ->push(['data' => ['me' => ['id' => 'abc']]])]);

    expect(app(GraphQLClient::class)->send(Operation::Me))->toBe(['me' => ['id' => 'abc']]);

    Http::assertSentCount(2);
    Sleep::assertSleptTimes(1);
    Sleep::assertSequence([Sleep::for(3000)->milliseconds()]);
});

it('gives up after the configured retries, saying how long to wait', function () {
    config()->set('autentique.retry.times', 1);

    Http::fake(['*' => Http::response(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '12'])]);

    $exception = thrown(RateLimited::class, fn() => app(GraphQLClient::class)->send(Operation::Me));

    expect($exception->retryAfter)->toBe(12)
        ->and($exception->operation)->toBe('me');

    Http::assertSentCount(2);
});

it('does not retry when retries are turned off', function () {
    config()->set('autentique.retry.times', 0);

    Http::fake(['*' => Http::response(['message' => 'Too Many Attempts.'], 429)]);

    expect(fn() => app(GraphQLClient::class)->send(Operation::Me))->toThrow(RateLimited::class);

    Http::assertSentCount(1);
});

it('never retries a server error, which may have been processed', function () {
    Http::fake(['*' => Http::response('<html>Bad gateway</html>', 502)]);

    expect(fn() => app(GraphQLClient::class)->send(Operation::CreateDocument, [], memoryFile()))
        ->toThrow(TransportFailed::class, 'HTTP 502');

    Http::assertSentCount(1);
});

it('never retries a connection failure, and keeps the token out of the message', function () {
    Http::fake(fn() => throw new ConnectionException('cURL error 28: Operation timed out'));

    $exception = thrown(TransportFailed::class, fn() => app(GraphQLClient::class)->send(Operation::Me));

    expect($exception->getMessage())->toContain('may have been processed')
        ->and(str_contains($exception->getMessage(), 'the-token'))->toBeFalse()
        ->and($exception->getPrevious())->toBeInstanceOf(ConnectionException::class);

    Http::assertSentCount(0);
});

it('carries the request id Autentique returns', function () {
    Http::fake(['*' => Http::response(['message' => 'unauthorized'], 401, ['X-Attq-Request-Id' => 'abc-123'])]);

    $exception = thrown(Unauthenticated::class, fn() => app(GraphQLClient::class)->send(Operation::Me));

    expect($exception->requestId)->toBe('abc-123')
        ->and($exception->status)->toBe(401);
});

it('reads the endpoint from the configuration', function () {
    config()->set('autentique.url', 'https://stub.test/graphql');
    Http::fake(['stub.test/*' => Http::response(['data' => ['me' => null]])]);

    app(GraphQLClient::class)->send(Operation::Me);

    Http::assertSent(fn(Request $request): bool => $request->url() === 'https://stub.test/graphql');
});

it('is the class bound to the contract', function () {
    expect(app(GraphQLClient::class))->toBeInstanceOf(Client::class);
});
