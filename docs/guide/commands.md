# Commands

## `autentique:schema`

```bash
php artisan autentique:schema                          # prints the schema as JSON
php artisan autentique:schema --output=schema.json     # writes it to a file
```

Asks Autentique for its schema by introspection, with the configured token, and
writes the answer as JSON. Autentique's documentation has no schema reference,
so this is the one complete description of what the API accepts: every type,
every argument, every enum value, including the ones the documentation never
mentions.

It is how the package keeps its own operations honest
([how the schema is refreshed](/spec/graphql-operations#refreshing-the-schema)),
and it is equally useful to an application exploring a field before asking
for it with a [raw query](/guide/raw-queries).

| Exit code | When |
|---|---|
| `0` | the schema was written or printed |
| `1` | Autentique refused the request; the reason is printed |
