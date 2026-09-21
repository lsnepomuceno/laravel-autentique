<?php

declare(strict_types=1);

use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\{Introspection, Schema};
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use LSNepomuceno\LaravelAutentique\Enums\ErrorCode;
use LSNepomuceno\LaravelAutentique\GraphQL\{Endpoint, Operation, OperationLoader};

require_once __DIR__ . '/../Support/schema.php';

/**
 * Every operation, checked against the API's schema before a consumer's
 * request is the thing that finds a mismatch
 * (docs/decisions/0003-operations-live-in-graphql-files.md).
 *
 * The schema is tests/Resources/schema.graphql. Until #34 replaces it with a
 * real introspection, it is assembled from the documentation, and its header
 * says so.
 */

/**
 * The schema an endpoint's operations are validated against.
 */
function endpointSchema(Endpoint $endpoint): Schema
{
    /** @var array<string, Schema> $schemas */
    static $schemas = [];

    $file = match ($endpoint) {
        Endpoint::Standard => 'schema.graphql',
        Endpoint::Corporate => 'schema-corporate.graphql',
    };

    return $schemas[$file] ??= BuildSchema::build((string) file_get_contents(packageRoot() . "/tests/Resources/{$file}"));
}

/**
 * The package's enums, each with the schema enum it mirrors.
 *
 * Every string backed enum in src/Enums that mirrors an API value set is listed
 * here, and the test below refuses one that is not, so a new enum cannot skip
 * the comparison. ErrorCode mirrors a documentation table, not a schema type.
 *
 * @return array<class-string<BackedEnum>, string>
 */
function mirroredEnums(): array
{
    return [];
}

it('validates every operation against the schema of its endpoint', function (Operation $operation) {
    $errors = DocumentValidator::validate(
        endpointSchema($operation->endpoint()),
        Parser::parse(app(OperationLoader::class)->load($operation)),
    );

    expect(array_map(fn(Error $error): string => $error->getMessage(), $errors))->toBe([]);
})->with(Operation::cases());

it('refuses an operation that asks for a field the API does not have', function () {
    // The gate has to be able to fail, on the mistake it exists to catch.
    $errors = DocumentValidator::validate(
        endpointSchema(Endpoint::Standard),
        Parser::parse('query { me { id favourite_colour } }'),
    );

    expect($errors)->not->toBe([]);
});

it('refuses a variable of the wrong type', function () {
    $errors = DocumentValidator::validate(
        endpointSchema(Endpoint::Standard),
        Parser::parse('query ($id: String) { document(id: $id) { id } }'),
    );

    expect($errors)->not->toBe([]);
});

it('mirrors every value of each API enum, and nothing else', function () {
    // The schema carries the enums to compare against, whether or not the
    // package has grown its own yet.
    expect(endpointSchema(Endpoint::Standard)->getType('ActionEnum'))->toBeInstanceOf(EnumType::class);

    foreach (mirroredEnums() as $enum => $name) {
        $type = endpointSchema(Endpoint::Standard)->getType($name);

        expect($type)->toBeInstanceOf(EnumType::class);

        /** @var EnumType $type */
        $values = [];

        foreach ($type->getValues() as $value) {
            // A value the API deprecated is one the package does not offer.
            if (! $value->isDeprecated()) {
                $values[] = $value->name;
            }
        }

        expect(array_map(fn(BackedEnum $case): string|int => $case->value, $enum::cases()))
            ->toEqualCanonicalizing($values);
    }
});

it('lists every enum that mirrors the API', function () {
    $enums = [];

    $files = glob(packageRoot() . '/src/Enums/*.php');

    foreach ($files === false ? [] : $files as $file) {
        $enums[] = 'LSNepomuceno\\LaravelAutentique\\Enums\\' . basename($file, '.php');
    }

    $unmapped = array_diff($enums, array_keys(mirroredEnums()), [ErrorCode::class]);

    expect(array_values($unmapped))->toBe([]);
});

it('prints an introspection back into the schema it came from', function () {
    // The round trip `autentique:schema` and `composer schema:print` perform
    // when #34 is done, proved here on the assembled schema.
    $schema = endpointSchema(Endpoint::Standard);
    $printed = printIntrospection(Introspection::fromSchema($schema), '2026-09-21');

    $rebuilt = BuildSchema::build($printed);

    expect($printed)->toContain('INTROSPECTED on 2026-09-21')
        ->and($rebuilt->getType('Document'))->not->toBeNull()
        ->and(DocumentValidator::validate($rebuilt, Parser::parse(app(OperationLoader::class)->load(Operation::CreateDocument))))->toBe([]);
});
