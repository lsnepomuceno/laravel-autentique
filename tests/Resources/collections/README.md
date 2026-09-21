# Autentique's collections

The collections Autentique publishes for its API, downloaded on 2026-09-21:

| File | Where it comes from |
|---|---|
| `autentique-v2.postman_collection.json` | attached to the API v2 introduction, "Para importar e usar no Postman" |
| `autentique-oauth.postman_collection.json` | attached to OAuth 2.0, "Obtaining and using tokens" |
| `autentique-altair.collection.json` | embedded in Autentique's Altair build at `altair.autentique.com.br` (built 2025-10-27), where it is loaded into the browser as the "Autentique" collection; extracted from `main.*.js` and written out as JSON, otherwise unchanged |

The Altair build embeds no schema: its Docs tab introspects with the visitor's
own token, so it cannot stand in for #34.

`tests/GraphQL/SchemaTest.php` validates every GraphQL operation in them against
the committed schema. They are Autentique's own examples, and the OAuth
collection's requests carry tests that expect no errors, so a schema they do not
validate against is a schema that is wrong.

The v2 collection's last request is a sample of the deprecated, URL encoded
webhook and holds no GraphQL.
