# GraphQL operations

How an operation is added to the package, or changed. The reasoning is in
[0003](../decisions/0003-operations-live-in-graphql-files.md); this page is the
procedure.

The files, the enum naming them and the loader shipped with #7. The check
against the schema is built by #9, and until it lands step 3 below is the
specification it is built against.

## Where things live

```
src/Resources/graphql/
├── queries/        one file per query:    document.graphql, documents.graphql
├── mutations/      one file per mutation: createDocument.graphql
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
3. **Validate it against the schema.** The suite parses and validates every file
   against the committed SDL, so a misspelled field or a wrong argument type
   fails `composer test`.
4. **Model the answer.** Every field the file selects becomes a typed property
   of a value object ([0008](../decisions/0008-responses-are-typed-value-objects.md)).
   A field selected and not modelled is a field nobody can read.
5. **Test it** with a fixture: the response the documentation shows, or one
   recorded from the sandbox with personal data replaced. Assert the variables
   sent as well as the object returned.
6. **Document it** in the guide page for its area, and in
   [the public API](public-api.md).

## What the suite checks today

`tests/GraphQL/OperationTest.php`:

- every enum case has a file and every file has a case;
- every operation is named after its file;
- every fragment is named after its file, and every fragment is used;
- no argument in any file is a literal, only a variable;
- the loader appends each fragment once, recursively, fails on a fragment no
  file defines, leaves inline fragments alone, and reads each file once.

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

A refresh that makes an operation invalid fails the suite, which is the point:
the change is seen here before a consumer's request fails.
