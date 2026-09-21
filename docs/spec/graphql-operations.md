# GraphQL operations

How an operation is added to the package, or changed. The reasoning is in
[0003](../decisions/0003-operations-live-in-graphql-files.md); this page is the
procedure.

The files, the enum naming them and the loader shipped with #7, and the check
against the schema with #9.

**The committed schema is assembled, not introspected**, until #34 replaces it:
the documentation never names some of the types its examples select, and
introspection needs a token this repository does not hold. The schema's header
says so, and #34 lists every name in it that is a guess.

## Where things live

```
src/Resources/graphql/
├── queries/        one file per query:    document.graphql, documents.graphql
├── mutations/      one file per mutation: createDocument.graphql
├── corporate/      the same two, for the Corporate endpoint
└── fragments/      shared selections:     DocumentFields.graphql
```

- **One operation per file.** The operation's name is the file's name:
  `mutations/createDocument.graphql` holds `mutation createDocument(…)`, and
  the answer arrives under `data.createDocument`.
- **Variables are named after the argument they feed**, snake case included:
  `$folder_id` for `folder_id:`. The array a caller's code builds then reads
  like the API's own documentation.
- **Values are variables, always** ([invariant 2](invariants.md)). An operation
  declares every value it needs as a `$variable` with its GraphQL type.
- **A selection used in more than one operation is a fragment**, in its own file
  named after it. An operation spreads it (`...DocumentFields`) and the loader
  appends the fragment, and any fragment it spreads in turn, each once.

## Adding an operation

1. **Write the file.** Copy the operation from the Autentique documentation when
   it has one, turn every literal argument into a variable, and select the
   fields the package will model, reusing fragments where the selection
   already exists.
2. **Add the enum case** to `LSNepomuceno\LaravelAutentique\GraphQL\Operation`,
   whose value is the file's path without the extension. Whether it is a query
   or a mutation, which endpoint it targets and the field its answer arrives
   under all follow from that path.
3. **Validate it against the schema.** `tests/GraphQL/SchemaTest.php` parses and
   validates every file against `tests/Resources/schema.graphql` with
   `webonyx/graphql-php`, a development dependency only, so a misspelled field
   or a wrong argument type fails `composer test`. If the API has something the
   committed schema lacks, refresh the schema first (below); never edit it to
   make an operation pass.
4. **Model the answer.** Every field the file selects becomes a typed property
   of a value object ([0008](../decisions/0008-responses-are-typed-value-objects.md)).
   A field selected and not modelled is a field nobody can read.
5. **Test it** with a fixture: the response the documentation shows, or one
   recorded from the sandbox with personal data replaced. Assert the variables
   sent as well as the object returned.
6. **Document it** in the guide page for its area, and in
   [the public API](public-api.md).

## Enums

A package enum that mirrors an API enum (`ActionEnum`, `DeliveryMethodEnum`, …)
is listed in `mirroredEnums()` in `tests/GraphQL/SchemaTest.php`, and the suite
compares their values: every non deprecated value of the schema's enum, and
nothing else. An enum in `src/Enums` that is not listed fails the suite, so a
new one cannot skip the comparison.

## What the suite checks today

`tests/GraphQL/OperationTest.php`:

- every enum case has a file and every file has a case;
- every operation is named after its file;
- every fragment is named after its file, and every fragment is used;
- no argument in any file is a literal, only a variable;
- the loader appends each fragment once, recursively, fails on a fragment no
  file defines, leaves inline fragments alone, and reads each file once.

`tests/GraphQL/SchemaTest.php`:

- every operation validates against the schema of its endpoint:
  `tests/Resources/schema.graphql` for the standard one,
  `tests/Resources/schema-corporate.graphql` for Corporate, both assembled;
- the check fails on an unknown field and on a variable of the wrong type;
- every GraphQL example in Autentique's own collections, the two Postman ones
  and the one its Altair build embeds, validates against the standard schema
  too
  (`tests/Resources/collections/`): they are the
  closest thing to ground truth without a token, and a schema they refuse is
  wrong;
- every mirrored enum has exactly the schema's non deprecated values;
- an introspection prints back into a schema the operations validate against.

## Changing an operation

Selecting a new field is a minor release: the value object gains a nullable
property. Removing a field, or making a nullable property required, is a major
release, because the value objects are public.

## Refreshing the schema

The Autentique documentation has no schema reference, so the schema is taken by
introspection, which needs a valid token. It is refreshed by the maintainer,
deliberately, not by CI:

- when Autentique announces a change;
- before a release;
- when an operation fails against the API while passing against the committed
  schema.

```bash
AUTENTIQUE_TOKEN=… vendor/bin/testbench autentique:schema --output=tests/Resources/introspection.json
composer schema:print
composer test
```

`autentique:schema` writes the introspection as JSON, and needs no development
dependency, so it ships ([the command](../guide/commands.md)).
`composer schema:print` turns that JSON into `tests/Resources/schema.graphql`
with `webonyx/graphql-php`, and stamps the date in its header. Commit both.

A refresh that makes an operation invalid fails the suite, which is the point:
the change is seen here before a consumer's request fails.

`.graphqlconfig` points an IDE's GraphQL support at the same schema, so the
operation files are completed and checked as they are written.
