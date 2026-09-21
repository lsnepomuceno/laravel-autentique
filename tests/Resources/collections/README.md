# Autentique's Postman collections

The two collections Autentique attaches to its documentation, downloaded on
2026-09-21 and kept unchanged:

| File | Attached to |
|---|---|
| `autentique-v2.postman_collection.json` | the API v2 introduction, "Para importar e usar no Postman" |
| `autentique-oauth.postman_collection.json` | OAuth 2.0, "Obtaining and using tokens" |

`tests/GraphQL/SchemaTest.php` validates every GraphQL operation in them against
the committed schema. They are Autentique's own examples, and the OAuth
collection's requests carry tests that expect no errors, so a schema they do not
validate against is a schema that is wrong.

The v2 collection's last request is a sample of the deprecated, URL encoded
webhook and holds no GraphQL.
