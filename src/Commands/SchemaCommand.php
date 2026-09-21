<?php

declare(strict_types=1);

namespace LSNepomuceno\LaravelAutentique\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LSNepomuceno\LaravelAutentique\Contracts\GraphQLClient;
use LSNepomuceno\LaravelAutentique\Exceptions\AutentiqueException;
use LSNepomuceno\LaravelAutentique\GraphQL\Operation;

/**
 * Asks Autentique for its schema, with the configured token.
 *
 * The documentation has no schema reference, so this is how the package learns
 * what the API accepts. It writes the introspection as JSON; turning that into
 * the SDL the suite validates against is a development step
 * (docs/spec/graphql-operations.md).
 */
final class SchemaCommand extends Command
{
    /** @var string */
    protected $signature = 'autentique:schema
        {--output= : Write the introspection to this file instead of printing it}';

    /** @var string */
    protected $description = "Fetch the Autentique API's schema by introspection, as JSON";

    public function handle(GraphQLClient $client, Filesystem $files): int
    {
        try {
            $data = $client->send(Operation::Introspection);
        } catch (AutentiqueException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $json = json_encode(
            [Operation::Introspection->field() => $data[Operation::Introspection->field()] ?? null],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n";

        $output = $this->option('output');

        if (! is_string($output) || $output === '') {
            $this->output->write($json);

            return self::SUCCESS;
        }

        $files->put($output, $json);
        $this->components->info("The schema was written to {$output}.");

        return self::SUCCESS;
    }
}
