# Raw queries

The package models the operations Autentique documents. When you need a field
it does not select, or an operation it does not ship, send the GraphQL
yourself:

```php
use LSNepomuceno\LaravelAutentique\Facades\Autentique;

$data = Autentique::query(
    'query ($id: UUID!) { document(id: $id) { id hashes { sha2 } } }',
    ['id' => $documentId],
);

$data['document']['hashes']['sha2'];
```

It returns the response's `data` as an array, and fails with the same
[exceptions](/guide/errors) as everything else. It goes through the same
client, so `Http::fake()` reaches it and the token is the configured one.

## Pass values as variables

Write every value as a variable, as above, and never build the text by
concatenation:

```php
// Never this: a quote in $name changes the operation.
Autentique::query("mutation { createFolder(folder: { name: \"{$name}\" }) { id } }");
```

The package's own operations cannot do this: a test fails if one is written in
PHP at all ([invariant 2](/spec/invariants)). The escape hatch sends what you
give it, so the same care is yours.

## Better still, open an issue

A field worth selecting for you is probably worth selecting for everyone. If you
find yourself using this for a documented field or operation, an
[issue](https://github.com/lsnepomuceno/laravel-autentique/issues) puts it in the
package, typed.
