<?php

declare(strict_types=1);

use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\{Introspection, Schema};
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use LSNepomuceno\LaravelAutentique\Enums;
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
    return [
        Enums\Action::class => 'ActionEnum',
        Enums\Context::class => 'ContextEnum',
        Enums\DocumentStatus::class => 'DocumentStatusEnum',
        Enums\OrderDirection::class => 'OrderByEnum',
        Enums\DateFormat::class => 'DateFormatEnum',
        Enums\DeliveryMethod::class => 'DeliveryMethodEnum',
        Enums\DocumentType::class => 'DocumentTypeEnum',
        Enums\EmailTemplateType::class => 'EmailTemplateTypeEnum',
        Enums\FallbackBehavior::class => 'FallbackBehaviorEnum',
        Enums\Footer::class => 'FooterEnum',
        Enums\FolderRole::class => 'FolderRoleEnum',
        Enums\FolderType::class => 'FolderTypeEnum',
        Enums\FooterType::class => 'FooterTypeEnum',
        Enums\PositionElement::class => 'PositionElementEnum',
        Enums\Reminder::class => 'ReminderEnum',
        Enums\SignatureAppearance::class => 'SignatureAppearanceEnum',
        Enums\SessionBehavior::class => 'SessionBehaviorEnum',
        Enums\SignerType::class => 'SignerTypeEnum',
        Enums\VerificationType::class => 'SecurityVerificationEnum',
        Enums\WhatsappTemplate::class => 'WhatsappTemplateEnum',
    ];
}

/**
 * The package's enums that mirror an enum of the Corporate schema.
 *
 * @return array<class-string<BackedEnum>, string>
 */
function corporateMirroredEnums(): array
{
    return [
        Enums\ChildOrganizationPlan::class => 'PipefyPlansEnum',
        Enums\CreditsRecurrence::class => 'CreditsRecurrenceEnum',
        Enums\CustomPlan::class => 'CustomPlansEnum',
        Enums\IntervalType::class => 'IntervalTypeEnum',
        Enums\Tier::class => 'TierEnum',
        Enums\WebhookEndpointType::class => 'WebhookEndpointTypeEnum',
        Enums\WebhookFormat::class => 'WebhookFormatEnum',
    ];
}

/**
 * Enums that mirror something other than a schema enum: a documentation table,
 * or a value the schema types as a plain string.
 *
 * @return list<class-string<BackedEnum>>
 */
function unmirroredEnums(): array
{
    return [
        // The table of codes on the documentation's error page.
        ErrorCode::class,
        // LocaleInput.language is a String in the schema; the documentation
        // lists the three values it accepts.
        Enums\Language::class,
        // Webhook event types are dotted strings in the payload. The Corporate
        // schema has an enum for registering endpoints, spelt differently.
        Enums\WebhookEventType::class,
        // The keys of the member permissions input, not an enum.
        Enums\MemberPermission::class,
        // OAuth scopes are strings of the authorization URL, not of the schema.
        Enums\OAuthScope::class,
    ];
}

it('validates every operation against the schema of its endpoint', function (Operation $operation) {
    $errors = DocumentValidator::validate(
        endpointSchema($operation->endpoint()),
        Parser::parse(app(OperationLoader::class)->load($operation)),
    );

    expect(array_map(fn(Error $error): string => $error->getMessage(), $errors))->toBe([]);
})->with(Operation::cases());

/**
 * Every GraphQL operation in Autentique's Postman collections, by collection
 * and request name.
 *
 * @return array<string, array{0: string}>
 */
function officialExamples(): array
{
    $examples = [];

    $files = glob(packageRoot() . '/tests/Resources/collections/*.postman_collection.json');

    foreach ($files === false ? [] : $files as $file) {
        $collection = json_decode((string) file_get_contents($file), true);
        $pending = is_array($collection) && is_array($collection['item'] ?? null) ? $collection['item'] : [];

        while ($pending !== []) {
            $item = array_shift($pending);

            if (! is_array($item)) {
                continue;
            }

            if (is_array($item['item'] ?? null)) {
                $pending = [...$pending, ...$item['item']];

                continue;
            }

            $request = is_array($item['request'] ?? null) ? $item['request'] : [];
            $query = officialQuery(is_array($request['body'] ?? null) ? $request['body'] : []);

            if ($query !== null) {
                $name = is_string($item['name'] ?? null) ? $item['name'] : '?';
                $examples[basename($file, '.postman_collection.json') . ': ' . $name] = [$query];
            }
        }
    }

    return $examples;
}

/**
 * The GraphQL document of a Postman request body, whichever way it is sent.
 *
 * @param  array<mixed>  $body
 */
function officialQuery(array $body): ?string
{
    $payload = match ($body['mode'] ?? null) {
        'raw' => is_string($body['raw'] ?? null) ? json_decode($body['raw'], true) : null,
        'formdata' => (function () use ($body): mixed {
            foreach (is_array($body['formdata'] ?? null) ? $body['formdata'] : [] as $part) {
                if (is_array($part) && ($part['key'] ?? null) === 'operations' && is_string($part['value'] ?? null)) {
                    return json_decode($part['value'], true);
                }
            }

            return null;
        })(),
        default => null,
    };

    return is_array($payload) && is_string($payload['query'] ?? null) ? $payload['query'] : null;
}

it('validates every example in Autentique\'s own Postman collections', function (string $query) {
    $errors = DocumentValidator::validate(endpointSchema(Endpoint::Standard), Parser::parse($query));

    expect(array_map(fn(Error $error): string => $error->getMessage(), $errors))->toBe([]);
})->with(officialExamples());

it('finds the examples it exists to check', function () {
    expect(count(officialExamples()))->toBe(17);
});

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

    $mirrored = [];

    foreach (mirroredEnums() as $enum => $name) {
        $mirrored[] = [$enum, $name, Endpoint::Standard];
    }

    foreach (corporateMirroredEnums() as $enum => $name) {
        $mirrored[] = [$enum, $name, Endpoint::Corporate];
    }

    foreach ($mirrored as [$enum, $name, $endpoint]) {
        $type = endpointSchema($endpoint)->getType($name);

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

    $unmapped = array_diff($enums, array_keys(mirroredEnums()), array_keys(corporateMirroredEnums()), unmirroredEnums());

    expect(array_values($unmapped))->toBe([]);
});

it('names every webhook event the Corporate endpoint registers, and knows the two it does not', function () {
    $type = endpointSchema(Endpoint::Corporate)->getType('WebhookEventTypeEnum');

    expect($type)->toBeInstanceOf(EnumType::class);

    /** @var EnumType $type */
    $registrable = array_map(fn($value): string => $value->name, $type->getValues());

    $names = array_values(array_filter(
        array_map(fn(Enums\WebhookEventType $event): ?string => $event->endpointName(), Enums\WebhookEventType::cases()),
        fn(?string $name): bool => $name !== null,
    ));

    expect($names)->toEqualCanonicalizing($registrable)
        ->and(Enums\WebhookEventType::SignatureBiometricReset->endpointName())->toBeNull()
        ->and(Enums\WebhookEventType::SignatureDeliveryFailed->endpointName())->toBeNull();
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
