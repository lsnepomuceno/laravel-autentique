<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\GraphQL;

use Illuminate\Filesystem\Filesystem;
use LSNepomuceno\LaravelAutentique\Exceptions\InvalidOperation;

/**
 * Reads an operation's `.graphql` file and appends the fragments it spreads.
 *
 * A fragment is written once, in `fragments/{Name}.graphql`, and an operation
 * spreads it with `...Name`. The loader finds every spread, recursively, and
 * appends each fragment exactly once, so the document sent is complete and
 * valid on its own (docs/spec/graphql-operations.md).
 *
 * The text is read from disk once per operation per process and kept, and it is
 * never altered: values travel as variables (docs/spec/invariants.md, rule 2).
 */
final class OperationLoader
{
    /** @var array<string, string> */
    private array $loaded = [];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $directory = __DIR__ . '/../Resources/graphql',
    ) {}

    /**
     * The complete document for an operation: its own text, then every fragment
     * it needs.
     *
     * @throws InvalidOperation
     */
    public function load(Operation $operation): string
    {
        return $this->loaded[$operation->value] ??= $this->assemble($operation);
    }

    /**
     * @throws InvalidOperation
     */
    private function assemble(Operation $operation): string
    {
        $document = $this->read($operation->value);
        $fragments = [];
        $pending = $this->spreads($document);

        while ($pending !== []) {
            $name = array_shift($pending);

            if (isset($fragments[$name])) {
                continue;
            }

            if (! $this->files->isFile($this->path("fragments/{$name}"))) {
                throw InvalidOperation::unknownFragment($name, $operation->operationName());
            }

            $fragments[$name] = $this->read("fragments/{$name}");
            $pending = [...$pending, ...$this->spreads($fragments[$name])];
        }

        ksort($fragments);

        return trim(implode("\n\n", [$document, ...array_values($fragments)])) . "\n";
    }

    /**
     * The names of the fragments a piece of GraphQL spreads.
     *
     * An inline fragment, `... on Type`, has no name to load and is skipped.
     *
     * @return list<string>
     */
    private function spreads(string $graphql): array
    {
        preg_match_all('/\.\.\.\s*(?!on\b)([A-Za-z_][A-Za-z0-9_]*)/', $graphql, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @throws InvalidOperation
     */
    private function read(string $name): string
    {
        $path = $this->path($name);

        if (! $this->files->isFile($path)) {
            throw InvalidOperation::missing($name . '.graphql');
        }

        return trim($this->files->get($path));
    }

    private function path(string $name): string
    {
        return $this->directory . '/' . $name . '.graphql';
    }
}
