# Configuration

Every key has an environment variable and a default, so most applications never
publish the file. When you need to:

```bash
php artisan vendor:publish --tag=autentique-config
```

That writes `config/autentique.php` into the application.

## The keys

| Key | Environment | Default | |
|---|---|---|---|
| `token` | `AUTENTIQUE_TOKEN` | none | the API token, sent as a Bearer token |
| `url` | `AUTENTIQUE_URL` | `https://api.autentique.com.br/v2/graphql` | the GraphQL endpoint |
| `sandbox` | `AUTENTIQUE_SANDBOX` | `false` | whether documents are created as sandbox documents when a call does not say |
| `timeout` | `AUTENTIQUE_TIMEOUT` | `30` | seconds to wait for a response |

Keys are added by the features that read them, and each is listed here when it
lands.

## Scalars only

Every value in the file is a string, a number, a boolean or null, so
`php artisan config:cache` keeps working. If you publish the file and edit it,
keep it that way: an enum or an object in a config file fails at `config:cache`,
typically on the deployment that first runs it.

## Changing the endpoint

`url` exists for tests against a local stub and for Autentique's Corporate
endpoint. There is no sandbox endpoint: sandbox is an argument of the calls that
accept it, with `sandbox` as its default.
