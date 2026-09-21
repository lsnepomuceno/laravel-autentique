# Laravel Autentique

A Laravel client for the [Autentique](https://www.autentique.com.br) GraphQL
API v2: documents, signers, folders and webhooks.

**Under construction.** Nothing is released yet, and the work towards 1.0.0 is
tracked in [#1](https://github.com/lsnepomuceno/laravel-autentique/issues/1).
It replaces
[`lsnepomuceno/laravel-autentique-v2`](https://github.com/lsnepomuceno/laravel-autentique-v2),
which is no longer maintained.

## Compatibility

| | |
|---|---|
| PHP | 8.4.1 to 8.5 |
| Laravel | 13 |

## Development

```bash
composer check          # everything CI runs: pint --test, phpstan, deps, pest, type coverage
docker compose -f .docker/compose.yaml run --rm php85 composer check
```

## License

MIT. See [LICENSE.md](LICENSE.md).
