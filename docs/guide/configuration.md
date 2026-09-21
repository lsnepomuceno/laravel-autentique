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
| `corporate_url` | `AUTENTIQUE_CORPORATE_URL` | `https://api.autentique.com.br/v2/graphql/corporate` | the Corporate endpoint; see [Corporate](/guide/corporate) |
| `timeout` | `AUTENTIQUE_TIMEOUT` | `30` | seconds to wait for a response |
| `retry.times` | `AUTENTIQUE_RETRY_TIMES` | `2` | retries of a request Autentique refused with HTTP 429; `0` turns retrying off |
| `webhooks.secret` | `AUTENTIQUE_WEBHOOK_SECRET` | none | the endpoint's secret, from the dashboard; see [webhooks](/guide/webhooks) |
| `webhooks.path` | `AUTENTIQUE_WEBHOOK_PATH` | none | where the package listens; none registers no route |
| `webhooks.middleware` | | `[]` | middleware for that route besides the signature check; never `web` |
| `webhooks.deduplicate` | `AUTENTIQUE_WEBHOOK_DEDUPLICATE` | none | seconds to remember each event id and drop a repeat |
| `webhooks.cache_store` | `AUTENTIQUE_WEBHOOK_CACHE_STORE` | the default | the cache store remembering them |
| `retry.sleep` | `AUTENTIQUE_RETRY_SLEEP` | `1000` | milliseconds to wait, times the attempt number, when Autentique sends no `Retry-After` |

Keys are added by the features that read them, and each is listed here when it
lands.

## Scalars only

Every value in the file is a string, a number, a boolean, null or a list of strings, so
`php artisan config:cache` keeps working. If you publish the file and edit it,
keep it that way: an enum or an object in a config file fails at `config:cache`,
typically on the deployment that first runs it.

## Retries

Only a request Autentique refused, with HTTP 429, is retried. A timeout or a
server error never is, because it may have been processed. See
[errors](/guide/errors#rate-limits-and-retries).

## Changing the endpoint

`url` exists for tests against a local stub and for Autentique's Corporate
endpoint. There is no sandbox endpoint: sandbox is an argument of the calls that
accept it, with `sandbox` as its default.
