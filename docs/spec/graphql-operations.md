# GraphQL operations

How an operation is added to the package, or changed. The reasoning is in
[0003](../decisions/0003-operations-live-in-graphql-files.md); this page is the
procedure.

The loader, the operation enum and the schema check are built by #7 and #9.
Until they land, this page is the specification they are built against, and
each of those pull requests updates it to match what shipped.

## Where things live

```
src/Resources/graphql/
├── queries/        one file per query:    document.graphql, documents.graphql
├── mutations/      one file per mutation: createDocument.graphql
└── fragments/      shared selections:     DocumentFields.graphql
```

- **One operation per file.** The operation's name is the file's name:
  `mutations/createDocument.graphql` holds `mutation createDocument(…)`.
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
2. **Add the enum case** naming it, with whether it is a query or a mutation and
   which endpoint it targets.
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
