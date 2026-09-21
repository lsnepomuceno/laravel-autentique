# Commands

## `autentique:check`

```bash
php artisan autentique:check
```

Answers whether this application can talk to Autentique, before anything is
sent: the endpoint, whether documents are sandbox by default, whether the token
is accepted, whose account it is, and how many documents and verification
credits are left. It costs one `me` query.

| Exit code | When |
|---|---|
| `0` | the token was accepted |
| `1` | no token, a rejected token, a rate limit, or an unreachable endpoint; the reason is printed |

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
