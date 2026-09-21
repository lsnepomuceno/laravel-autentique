<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Testing;

use Closure;
use Illuminate\Container\Container;
use LSNepomuceno\LaravelAutentique\Contracts\{Autentique, FileSource, GraphQLClient};
use LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException;
use LSNepomuceno\LaravelAutentique\Facades\Autentique as AutentiqueFacade;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\Support\Payload;
use PHPUnit\Framework\Assert;

/**
 * What a consuming application asserts about its own use of Autentique.
 *
 * Installed with `Autentique::fake()`, it takes the place of the GraphQL client
 * in the container, so the facade, the builder and every `Api\` class reach it
 * and nothing reaches the network. Nothing is billed, no token is needed, and
 * no signer receives anything.
 *
 * Every call is recorded. Every answer is shaped as Autentique would shape it
 * and built from what was sent, unless the test says otherwise with
 * `respond()` or `fail()`.
 */
final class AutentiqueFake implements GraphQLClient
{
    /** @var list<SentOperation> */
    private array $sent = [];

    /** @var array<string, Closure(array<string, mixed>): mixed> */
    private array $answers = [];

    /** @var array<string, AutentiqueException> */
    private array $failures = [];

    /** @var ?Closure(string, array<string, mixed>): array<string, mixed> */
    private ?Closure $queryAnswer = null;

    private readonly FakeAnswers $defaults;

    /**
     * @param  ?AutentiqueFake  $parent  The fake a token-scoped copy records into.
     */
    private function __construct(
        private readonly ?self $parent = null,
        #[\SensitiveParameter]
        private readonly ?string $token = null,
    ) {
        $this->defaults = new FakeAnswers();
    }

    /**
     * Takes the place of the GraphQL client, and forgets every instance that
     * was built around the real one.
     */
    public static function install(Container $container): self
    {
        $fake = new self();

        $container->instance(GraphQLClient::class, $fake);
        $container->forgetInstance(Autentique::class);
        AutentiqueFacade::clearResolvedInstance(Autentique::class);

        return $fake;
    }

    /**
     * Answers an operation with this value under its field, or with what the
     * closure returns for the variables sent.
     *
     * @param  mixed|Closure(array<string, mixed>): mixed  $answer
     */
    public function respond(Operation $operation, mixed $answer): self
    {
        $this->answers[$operation->value] = $answer instanceof Closure ? $answer : fn(): mixed => $answer;
        unset($this->failures[$operation->value]);

        return $this;
    }

    /**
     * Throws this exception whenever the operation is sent. It is recorded
     * all the same.
     */
    public function fail(Operation $operation, AutentiqueException $exception): self
    {
        $this->failures[$operation->value] = $exception;

        return $this;
    }

    /**
     * Answers `Autentique::query()` with what the closure returns.
     *
     * @param  Closure(string, array<string, mixed>): array<string, mixed>  $answer
     */
    public function respondToQuery(Closure $answer): self
    {
        $this->queryAnswer = $answer;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     *
     * @throws AutentiqueException
     */
    #[\Override]
    public function send(Operation $operation, array $variables = [], ?FileSource $file = null): array
    {
        $root = $this->root();
        $root->sent[] = new SentOperation($operation, $variables, $file?->name(), token: $this->token);

        if (isset($root->failures[$operation->value])) {
            throw $root->failures[$operation->value];
        }

        $answer = isset($root->answers[$operation->value])
            ? ($root->answers[$operation->value])($variables)
            : $root->defaults->for($operation, $variables);

        return [$operation->field() => $answer];
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    #[\Override]
    public function raw(string $document, array $variables = []): array
    {
        $root = $this->root();
        $root->sent[] = new SentOperation(null, $variables, query: $document, token: $this->token);

        return $root->queryAnswer === null ? [] : ($root->queryAnswer)($document, $variables);
    }

    /**
     * A copy recording into this fake, marking what it sends with the token.
     */
    #[\Override]
    public function withToken(#[\SensitiveParameter] string $token): self
    {
        return new self($this->root(), $token);
    }

    /**
     * Every call of one operation, or every call when none is named.
     *
     * @return list<SentOperation>
     */
    public function sent(?Operation $operation = null): array
    {
        return array_values(array_filter(
            $this->root()->sent,
            fn(SentOperation $sent): bool => $operation === null || $sent->operation === $operation,
        ));
    }

    /**
     * @param  ?Closure(array<string, mixed>): bool  $matching  Given the variables sent.
     */
    public function assertSent(Operation $operation, ?Closure $matching = null): self
    {
        Assert::assertNotEmpty(
            $this->matching($operation, $matching),
            "Expected {$operation->operationName()} to be sent" . ($matching === null ? '.' : ' matching the callback.'),
        );

        return $this;
    }

    public function assertNotSent(Operation $operation): self
    {
        Assert::assertEmpty($this->sent($operation), "Expected {$operation->operationName()} not to be sent.");

        return $this;
    }

    public function assertSentTimes(Operation $operation, int $times): self
    {
        $count = count($this->sent($operation));

        Assert::assertSame($times, $count, "Expected {$operation->operationName()} to be sent {$times} times, it was sent {$count}.");

        return $this;
    }

    public function assertNothingSent(): self
    {
        Assert::assertEmpty($this->sent(), 'Expected nothing to be sent to Autentique.');

        return $this;
    }

    /**
     * A document was created. The callback receives what `createDocument`
     * sent: the document, the signers, and the uploaded file's name.
     *
     * @param  ?Closure(array<array-key, mixed>, list<array<array-key, mixed>>, ?string): bool  $matching
     */
    public function assertDocumentSent(?Closure $matching = null): self
    {
        $found = array_filter(
            $this->sent(Operation::CreateDocument),
            fn(SentOperation $sent): bool => $matching === null || $matching(...$this->documentArguments($sent)),
        );

        Assert::assertNotEmpty($found, 'Expected a document to be sent' . ($matching === null ? '.' : ' matching the callback.'));

        return $this;
    }

    public function assertDocumentSentTimes(int $times): self
    {
        return $this->assertSentTimes(Operation::CreateDocument, $times);
    }

    public function assertNoDocumentSent(): self
    {
        return $this->assertNotSent(Operation::CreateDocument);
    }

    /**
     * A signer was added to an existing document.
     *
     * @param  ?Closure(string, array<string, mixed>): bool  $matching  Given the document id and the signer sent.
     */
    public function assertSignerAdded(?Closure $matching = null): self
    {
        return $this->assertSent(Operation::CreateSigner, $matching === null ? null : function (array $variables) use ($matching): bool {
            $documentId = is_string($variables['document_id'] ?? null) ? $variables['document_id'] : '';
            $signer = is_array($variables['signer'] ?? null) ? $variables['signer'] : [];

            /** @var array<string, mixed> $signer */
            return $matching($documentId, $signer);
        });
    }

    /**
     * Requests were resent, to exactly these signatures when they are given.
     *
     * @param  ?list<string>  $publicIds
     */
    public function assertResent(?array $publicIds = null): self
    {
        return $this->assertSent(
            Operation::ResendSignatures,
            $publicIds === null ? null : fn(array $variables): bool => ($variables['public_ids'] ?? null) === $publicIds,
        );
    }

    /**
     * @param  ?Closure(array<string, mixed>): bool  $matching
     * @return list<SentOperation>
     */
    private function matching(Operation $operation, ?Closure $matching): array
    {
        return array_values(array_filter(
            $this->sent($operation),
            fn(SentOperation $sent): bool => $matching === null || $matching($sent->variables),
        ));
    }

    /**
     * @return array{0: array<array-key, mixed>, 1: list<array<array-key, mixed>>, 2: ?string}
     */
    private function documentArguments(SentOperation $sent): array
    {
        $payload = new Payload($sent->variables);

        return [
            $payload->object('document')?->all() ?? [],
            array_map(fn(Payload $signer): array => $signer->all(), $payload->list('signers')),
            $sent->fileName,
        ];
    }

    private function root(): self
    {
        return $this->parent ?? $this;
    }
}
