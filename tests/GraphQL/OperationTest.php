<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidOperation;
use LSNepomuceno\LaravelAutentique\GraphQL\Endpoint;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;
use LSNepomuceno\LaravelAutentique\GraphQL\OperationLoader;

/**
 * The operation files and the enum naming them move together
 * (docs/spec/graphql-operations.md).
 */

it('has a file for every case, and a case for every file', function () {
    $files = [];

    foreach (['queries', 'mutations', 'corporate'] as $directory) {
        foreach (graphqlFiles($directory) as $file) {
            $files[] = $directory . '/' . basename($file, '.graphql');
        }
    }

    $cases = array_map(fn(Operation $operation): string => $operation->value, Operation::cases());

    sort($files);
    sort($cases);

    expect($cases)->toBe($files);
});

it('names every operation after its file', function (Operation $operation) {
    $contents = (string) file_get_contents(graphqlDirectory() . "/{$operation->value}.graphql");

    preg_match('/^\s*(query|mutation)\s+([A-Za-z_]\w*)/', $contents, $match);

    expect($match[1] ?? null)->toBe($operation->isMutation() ? 'mutation' : 'query')
        ->and($match[2] ?? null)->toBe($operation->operationName());
})->with(Operation::cases());

it('names every fragment after its file, and uses every one', function () {
    $operations = implode("\n", array_map(
        fn(Operation $operation): string => app(OperationLoader::class)->load($operation),
        Operation::cases(),
    ));

    foreach (graphqlFiles('fragments') as $file) {
        $name = basename($file, '.graphql');

        expect((string) file_get_contents($file))->toStartWith("fragment {$name} on ")
            ->and($operations)->toContain("...{$name}");
    }
});

it('writes every value as a variable, never as a literal argument', function (Operation $operation) {
    // Invariant 2, at the level of the file: an argument is always `$name`.
    $contents = (string) file_get_contents(graphqlDirectory() . "/{$operation->value}.graphql");

    expect(preg_match('/\(\s*\w+\s*:\s*["\d]/', $contents))->toBe(0)
        ->and(preg_match('/,\s*\w+\s*:\s*["\d]/', $contents))->toBe(0);
})->with(Operation::cases());

it('knows how each operation travels', function () {
    expect(Operation::Me->isMutation())->toBeFalse()
        ->and(Operation::CreateDocument->isMutation())->toBeTrue()
        ->and(Operation::CreateDocument->uploadsAFile())->toBeTrue()
        ->and(Operation::CreateSigner->uploadsAFile())->toBeFalse()
        ->and(Operation::Documents->endpoint())->toBe(Endpoint::Standard)
        ->and(Operation::Documents->field())->toBe('documents');
});

it('appends the fragments an operation spreads, recursively, each once', function () {
    $document = app(OperationLoader::class)->load(Operation::Document);

    expect(substr_count($document, 'fragment DocumentFields on Document'))->toBe(1)
        ->and(substr_count($document, 'fragment SignatureFields on Signature'))->toBe(1)
        ->and(substr_count($document, 'fragment EventFields on Event'))->toBe(1)
        ->and(substr_count($document, 'fragment VerificationFields on'))->toBe(1)
        ->and($document)->toStartWith('query document(');
});

it('appends nothing to an operation that spreads nothing', function () {
    expect(app(OperationLoader::class)->load(Operation::DeleteFolder))
        ->toBe("mutation deleteFolder(\$id: UUID!) {\n  deleteFolder(id: \$id)\n}\n");
});

it('fails loudly on a fragment no file defines', function () {
    $directory = graphqlFixture(['queries/me' => 'query me { me { ...Nowhere } }']);

    new OperationLoader(new Filesystem(), $directory)->load(Operation::Me);
})->throws(InvalidOperation::class, 'spreads the fragment Nowhere');

it('fails loudly on a missing file', function () {
    new OperationLoader(new Filesystem(), graphqlFixture([]))->load(Operation::Me);
})->throws(InvalidOperation::class, 'queries/me.graphql');

it('leaves an inline fragment alone', function () {
    $directory = graphqlFixture(['queries/me' => 'query me { me { ... on User { id } } }']);

    expect(new OperationLoader(new Filesystem(), $directory)->load(Operation::Me))
        ->toBe("query me { me { ... on User { id } } }\n");
});

it('reads each file once per process', function () {
    $files = new class extends Filesystem {
        public int $reads = 0;

        #[\Override]
        public function get($path, $lock = false): string
        {
            $this->reads++;

            return parent::get($path, $lock);
        }
    };

    $loader = new OperationLoader($files, graphqlFixture(['queries/me' => 'query me { me { id } }']));

    expect($loader->load(Operation::Me))->toBe($loader->load(Operation::Me))
        ->and($files->reads)->toBe(1);
});
